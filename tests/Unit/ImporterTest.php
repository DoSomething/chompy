<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Chompy\Http\Controllers\ImportController;


class ImporterTest extends TestCase
{
    /**
     * Test validation rules for email subscription import.
     *
     * @return void
     */
    public function testValidationRulesForEmailSubscriptionImport()
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

        $request->replace([
            'source-detail' => 'test_opt_in',
            'upload-file' => $file,
        ]);

        $this->expectException(ValidationException::class);
        $controller->store($request, 'email-subscription');

        $request->replace([
            'topics' => [
                'community',
            ],
            'upload-file' => $file,
        ]);

        $this->expectException(ValidationException::class);
        $controller->store($request, 'email-subscription');
    }
}
