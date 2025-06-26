<?php
use PHPUnit\Framework\TestCase;

class ClassG2WPIDocImporterLogTest extends TestCase
{
    public function test_import_logs_success_and_errors()
    {
        $doc_id = 'abc123';
        $access_token = 'token';
        $params = [];
        // Mock Drive para error de metadatos
        $driveMock = $this->createMock(G2WPI_Drive::class);
        $driveMock->method('get_document_metadata')->willReturn(null);
        $driveMock->method('export_document_html')->willReturn('');
        $cleanerMock = $this->createMock(HtmlCleanerInterface::class);
        $loggerMock = $this->getMockBuilder(G2WPI_ImportLog::class)
            ->onlyMethods(['log_import'])
            ->getMock();
        $loggerMock->expects($this->once())
            ->method('log_import')
            ->with($doc_id, 0, 'error', 'No metadata');
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
        $importer->import($doc_id, $access_token, $params);

        // Mock Drive para error de contenido
        $driveMock2 = $this->createMock(G2WPI_Drive::class);
        $driveMock2->method('get_document_metadata')->willReturn(['name'=>'Test']);
        $driveMock2->method('export_document_html')->willReturn('');
        $loggerMock2 = $this->getMockBuilder(G2WPI_ImportLog::class)
            ->onlyMethods(['log_import'])
            ->getMock();
        $loggerMock2->expects($this->once())
            ->method('log_import')
            ->with($doc_id, 0, 'error', 'No content');
        $importer2 = new G2WPI_DocImporter($driveMock2, $cleanerMock, $loggerMock2);
        $importer2->import($doc_id, $access_token, $params);

        // Mock para éxito
        $driveMock3 = $this->createMock(G2WPI_Drive::class);
        $driveMock3->method('get_document_metadata')->willReturn(['name'=>'Test']);
        $driveMock3->method('export_document_html')->willReturn('<body>ok</body>');
        $cleanerMock3 = $this->createMock(HtmlCleanerInterface::class);
        $cleanerMock3->method('clean')->willReturn('ok');
        $loggerMock3 = $this->getMockBuilder(G2WPI_ImportLog::class)
            ->onlyMethods(['log_import'])
            ->getMock();
        $loggerMock3->expects($this->once())
            ->method('log_import')
            ->with($doc_id, 123, 'success');
        $importer3 = new G2WPI_DocImporter($driveMock3, $cleanerMock3, $loggerMock3);
        $importer3->import($doc_id, $access_token, $params);
    }
}
