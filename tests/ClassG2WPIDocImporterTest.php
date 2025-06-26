<?php
// tests/ClassG2WPIDocImporterTest.php
use PHPUnit\Framework\TestCase;

class ClassG2WPIDocImporterTest extends TestCase
{
    public function test_class_exists()
    {
        $this->assertTrue(class_exists('G2WPI_DocImporter'));
    }

    public function test_instance_methods()
    {
        // Crear mocks para las dependencias
        $driveMock = $this->createMock(G2WPI_Drive::class);
        $cleanerMock = $this->createMock(HtmlCleanerInterface::class);
        $loggerMock = $this->createMock(G2WPI_ImportLog::class);
        $importer = new G2WPI_DocImporter($driveMock, $cleanerMock, $loggerMock);
        $this->assertIsObject($importer);
        // $this->assertTrue(method_exists($importer, 'nombre_del_metodo'));
    }
}
