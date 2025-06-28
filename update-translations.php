<?php
// update-translations.php
// Ejecuta: php update-translations.php

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Dotenv\Dotenv;

// Cargar variables de entorno
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$apiKey = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY');
if (!$apiKey) {
    fwrite(STDERR, "No se encontró la variable de entorno GEMINI_API_KEY.\n");
    fwrite(STDERR, "Variables de entorno disponibles:\n");
    foreach ($_ENV as $k => $v) {
        fwrite(STDERR, "$k=$v\n");
    }
    exit(1);
}

function run($cmd) {
    $output = [];
    $result = 0;
    exec($cmd, $output, $result);
    if ($result !== 0) {
        fwrite(STDERR, "Error ejecutando: $cmd\n");
        exit($result);
    }
    return implode("\n", $output);
}

interface Translator {
    public function translatePoFile(string $poFile, string $langCode, string $potContent): Promise\PromiseInterface;
}

class GeminiPoTranslator implements Translator {
    private string $apiKey;
    private Client $client;

    public function __construct(string $apiKey, Client $client)
    {
        $this->apiKey = $apiKey;
        $this->client = $client;
    }

    public function translatePoFile(string $poFile, string $langCode, string $potContent): Promise\PromiseInterface
    {
        $existingPo = file_exists($poFile) ? file_get_contents($poFile) : '';
        $prompt = <<<PROMPT
Actúa como un generador de archivos .po para WordPress. Debes realizar una actualización tipo upsert: si la traducción de un término ya existe y es correcta, debe permanecer igual; si la traducción no es correcta, actualízala; si la traducción de un término no existe, créala. No elimines traducciones existentes que no estén en el archivo .pot. Devuélveme únicamente el contenido válido del archivo .po para el idioma $langCode, generado a partir del siguiente archivo base .pot y el archivo .po existente. No incluyas explicaciones, instrucciones, ni marcas de bloque, solo el contenido puro del archivo .po. El contenido del .pot es:

$potContent

El contenido actual del archivo .po es:

$existingPo
PROMPT;
        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'thinkingConfig' => [
                    'thinkingBudget' => -1
                ]
            ]
        ];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$this->apiKey}";
        return $this->client->postAsync($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($body)
        ])->then(function ($response) use ($poFile) {
            $json = json_decode($response->getBody(), true);
            $translation = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $translationClean = preg_replace('/^(\xEF\xBB\xBF|\s)+/u', '', $translation);
            file_put_contents($poFile, $translationClean);
            print "Actualizado: " . basename($poFile) . "\n";
        }, function ($reason) use ($poFile) {
            fwrite(STDERR, "Error actualizando ".basename($poFile).": $reason\n");
        });
    }
}

class PoUpdateManager {
    private Translator $translator;
    private string $potContent;
    private array $poFiles;

    public function __construct(Translator $translator, string $potContent, array $poFiles)
    {
        $this->translator = $translator;
        $this->potContent = $potContent;
        $this->poFiles = $poFiles;
    }

    public function updateAll(int $concurrency = 3): void
    {
        $promises = [];
        foreach ($this->poFiles as $poFile) {
            $langCode = basename($poFile, '.po');
            $langCode = str_replace('google-docs-importer-', '', $langCode);
            $promises[] = function() use ($poFile, $langCode) {
                return $this->translator->translatePoFile($poFile, $langCode, $this->potContent);
            };
        }
        $this->runConcurrent($promises, $concurrency);
    }

    private function runConcurrent(array $promiseFactories, int $concurrency): void
    {
        $total = count($promiseFactories);
        $running = [];
        $i = 0;
        $completed = 0;
        while ($completed < $total) {
            while (count($running) < $concurrency && $i < $total) {
                $factory = $promiseFactories[$i++];
                $promise = $factory();
                $running[] = $promise;
            }
            Promise\Utils::settle($running)->wait();
            $completed += count($running);
            $running = [];
        }
    }
}

// 1. Generar .pot
print "Generando archivo .pot...\n";
run('wp i18n make-pot . languages/google-docs-importer.pot');

// 2. Actualizar archivos .po con Gemini en paralelo
print "Actualizando archivos .po con Gemini...\n";
$potPath = __DIR__ . '/languages/google-docs-importer.pot';
$potContent = file_get_contents($potPath);
$poFiles = glob(__DIR__ . '/languages/*.po');
$client = new Client();
$translator = new GeminiPoTranslator($apiKey, $client);
$manager = new PoUpdateManager($translator, $potContent, $poFiles);
$manager->updateAll(3);
print "Todos los archivos .po han sido actualizados.\n";

// 3. Compilar archivos .mo
print "Compilando archivos .mo...\n";
chdir(__DIR__ . '/languages');
run('powershell -ExecutionPolicy Bypass -File ./compile-mo.ps1');
chdir(__DIR__);
print "Traducciones actualizadas y compiladas.\n";
