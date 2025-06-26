<?php
use PHPUnit\Framework\TestCase;

class ClassG2WPIDocImporterEdgeCasesTest extends TestCase
{
    public static $wp_insert_post_return = 456;
    public static $termsSet;

    private function getImporter($driveMeta, $driveHtml, $cleaned, $expectLog = false)
    {
        $driveMock = $this->createMock(G2WPI_Drive::class);
        $driveMock->method('get_document_metadata')->willReturn($driveMeta);
        $driveMock->method('export_document_html')->willReturn($driveHtml);
        $cleanerMock = $this->createMock(HtmlCleanerInterface::class);
        $cleanerMock->method('clean')->willReturn($cleaned);
        $loggerMock = $this->createMock(G2WPI_ImportLog::class);
        if ($expectLog) {
            $loggerMock->expects($this->once())->method('log_import');
        } else {
            $loggerMock->expects($this->never())->method('log_import');
        }
        return new G2WPI_DocImporter($driveMock, $cleanerMock, $loggerMock);
    }

    public function test_import_invalid_status_and_type()
    {
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
            function wp_insert_post($arr) { return ClassG2WPIDocImporterEdgeCasesTest::$wp_insert_post_return; }
        }
        if (!function_exists('is_wp_error')) {
            function is_wp_error($v) { return false; }
        }
        if (!function_exists('get_object_taxonomies')) {
            function get_object_taxonomies($type, $output) { return []; }
        }
        $importer = $this->getImporter(['name'=>'Test'], '<body>cuerpo</body>', 'cuerpo limpio', true);
        self::$wp_insert_post_return = 456;
        $result = $importer->import('id', 'token', ['status'=>'noexiste','post_type'=>'noexiste']);
        $this->assertEquals(456, $result);
    }

    public function test_import_with_term_id_sets_terms()
    {
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
            function wp_insert_post($arr) { return ClassG2WPIDocImporterEdgeCasesTest::$wp_insert_post_return; }
        }
        if (!function_exists('is_wp_error')) {
            function is_wp_error($v) { return false; }
        }
        // Simular taxonomía jerárquica
        if (!function_exists('get_object_taxonomies')) {
            function get_object_taxonomies($type, $output) {
                return [(object)['hierarchical'=>true,'name'=>'category']];
            }
        }
        if (!function_exists('wp_set_post_terms')) {
            function wp_set_post_terms($post_id, $terms, $taxonomy) {
                ClassG2WPIDocImporterEdgeCasesTest::$termsSet = [$post_id, $terms, $taxonomy];
            }
        }
        self::$termsSet = null;
        self::$wp_insert_post_return = 789;
        $importer = $this->getImporter(['name'=>'Test'], '<body>cuerpo</body>', 'cuerpo limpio', true);
        $result = $importer->import('id', 'token', ['term_id'=>99, 'post_type'=>'post']);
        $this->assertEquals(789, $result);
        $this->assertEquals([789, [99], 'category'], self::$termsSet);
    }
}
