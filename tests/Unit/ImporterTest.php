<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Chompy\Http\Controllers\ImportController;


class ImporterTest extends TestCase
{
    /**
     * Test that an email subscription import creates/updates users with first name and email address.
     *
     * @return void
     */
    public function testFirstNameAndEmailSavedForEmailSubscriptionImport()
    {
        $request = new Request;
        $file = UploadedFile::fake()->create('importer_test.csv');

        $request->replace([
            'source-detail' => 'test_opt_in',
            'topics' => [
                'community',
            ],
            'upload-file' => $file,
        ]);

        $controller = new ImportController();

        $response = $controller->store($request, 'email-subscription');

    }
}
