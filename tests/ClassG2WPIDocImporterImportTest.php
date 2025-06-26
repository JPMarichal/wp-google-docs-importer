<?php
use PHPUnit\Framework\TestCase;

class ClassG2WPIDocImporterImportTest extends TestCase
{
    public function test_import_successful()
    {
        $doc_id = 'abc123';
        $access_token = 'token';
        $params = [
            'author' => 1,
            'status' => 'publish',
            'post_type' => 'post',
            'term_id' => 0
        ];
        // Mock Drive
        $driveMock = $this->createMock(G2WPI_Drive::class);
        $driveMock->method('get_document_metadata')->willReturn(['name' => 'Título de prueba']);
        $driveMock->method('export_document_html')->willReturn('<body>contenido <b>importado</b></body>');
        // Mock Cleaner
        $cleanerMock = $this->createMock(HtmlCleanerInterface::class);
        $cleanerMock->method('clean')->willReturn('contenido limpio');
        // Mock Logger
        $loggerMock = $this->createMock(G2WPI_ImportLog::class);
        $loggerMock->expects($this->once())->method('log_import')->with($doc_id, 123);
        // Mock funciones de WP
        if (!function_exists('sanitize_text_field')) {
            function sanitize_text_field($str) { return $str; }
        }
        if (!function_exists('get_current_user_id')) {
            function get_current_user_id() { return 1; }
        }
        if (!function_exists('get_post_types')) {
            function get_post_types($args) { return ['post']; }
        }
        if (!function_exists('wp_insert_post')) {
            function wp_insert_post($arr) { return 123; }
        }
        if (!function_exists('is_wp_error')) {
            function is_wp_error($v) { return false; }
        }
        if (!function_exists('get_object_taxonomies')) {
            function get_object_taxonomies($type, $output) { return []; }
        }
        $importer = new G2WPI_DocImporter($driveMock, $cleanerMock, $loggerMock);
        $result = $importer->import($doc_id, $access_token, $params);
        $this->assertEquals(123, $result);
    }

    public function test_import_drive_error()
    {
        $doc_id = 'abc123';
        $access_token = 'token';
        $params = [];
        $driveMock = $this->createMock(G2WPI_Drive::class);
        $driveMock->method('get_document_metadata')->willReturn(null);
        $driveMock->method('export_document_html')->willReturn('');
        $cleanerMock = $this->createMock(HtmlCleanerInterface::class);
        $cleanerMock->method('clean')->willReturn('');
        $loggerMock = $this->createMock(G2WPI_ImportLog::class);
        if (!function_exists('sanitize_text_field')) {
            function sanitize_text_field($str) { return $str; }
        }
        if (!function_exists('get_current_user_id')) {
            function get_current_user_id() { return 1; }
        }
        if (!function_exists('get_post_types')) {
            function get_post_types($args) { return ['post']; }
        }
        if (!function_exists('wp_insert_post')) {
            function wp_insert_post($arr) { return 0; }
        }
        if (!function_exists('is_wp_error')) {
            function is_wp_error($v) { return false; }
        }
        if (!function_exists('get_object_taxonomies')) {
            function get_object_taxonomies($type, $output) { return []; }
        }
        $importer = new G2WPI_DocImporter($driveMock, $cleanerMock, $loggerMock);
        $result = $importer->import($doc_id, $access_token, $params);
        $this->assertEquals(0, $result);
    }
}
