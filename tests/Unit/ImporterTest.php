<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Chompy\Http\Controllers\ImportController;
use Illuminate\Validation\ValidationException;

class ImporterTest extends TestCase
{
    /**
     * Test validation error thrown without upload-file for email subscription import.
     *
     * @return void
     */
    public function testUploadFileValidationRuleForEmailSubscriptionImport()
    {
        $request = new Request;
        $file = UploadedFile::fake()->create('importer_test.csv');

        $request->replace([
            'source-detail' => 'test_opt_in',
            'topics' => [
                'community',
            ],
        ]);

        $controller = new ImportController();

        $this->expectException(ValidationException::class);
        $controller->store($request, 'email-subscription');
    }

    /**
     * Test validation error thrown without topics for email subscription import.
     *
     * @return void
     */
    public function testTopicsValidationRuleForEmailSubscriptionImport()
    {
        $request = new Request;
        $file = UploadedFile::fake()->create('importer_test.csv');

        $request->replace([
            'source-detail' => 'test_opt_in',
            'upload-file' => $file,
        ]);

        $controller = new ImportController();

        $this->expectException(ValidationException::class);
        $controller->store($request, 'email-subscription');
    }

    /**
     * Test validation error thrown without source-detail for email subscription import.
     *
     * @return void
     */
    public function testSourceDetailValidationRuleForEmailSubscriptionImport()
    {
        $request = new Request;
        $file = UploadedFile::fake()->create('importer_test.csv');

        $request->replace([
            'topics' => [
                'community',
            ],
            'upload-file' => $file,
        ]);

        $controller = new ImportController();

        $this->expectException(ValidationException::class);
        $controller->store($request, 'email-subscription');
    }
}
