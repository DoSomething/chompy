<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class ImporterTest extends TestCase
{
    /**
     * Test validation error thrown without upload-file for email subscription import.
     *
     * @return void
     */
    public function testUploadFileValidationRuleForEmailSubscriptionImport()
    {
        $user = \Chompy\User::forceCreate(['role' => 'admin']);
        $response = $this->be($user)->postJson('import/email-subscription', [
            '_token' => csrf_token(),
            'source-detail' => 'test_opt_in',
            'topics' => ['community'],
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test validation error thrown without topics for email subscription import.
     *
     * @return void
     */
    public function testTopicsValidationRuleForEmailSubscriptionImport()
    {
        $file = UploadedFile::fake()->create('importer_test.csv');

        $user = \Chompy\User::forceCreate(['role' => 'admin']);
        $response = $this->be($user)->postJson('import/email-subscription', [
            '_token' => csrf_token(),
            'source-detail' => 'test_opt_in',
            'upload-file' => $file,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test validation error thrown without source-detail for email subscription import.
     *
     * @return void
     */
    public function testSourceDetailValidationRuleForEmailSubscriptionImport()
    {
        $file = UploadedFile::fake()->create('importer_test.csv');

        $user = \Chompy\User::forceCreate(['role' => 'admin']);
        $response = $this->be($user)->postJson('import/email-subscription', [
            '_token' => csrf_token(),
            'topics' => ['community'],
            'upload-file' => $file,
        ]);

        $response->assertStatus(422);
    }
}
