<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Chompy\Models\ImportFile;

class ImportFileTest extends TestCase
{
    /**
     * Test expected behavior for incrementImportCount function.
     *
     * @return void
     */
    public function testIncrementImportCount()
    {
        $importFile = factory(ImportFile::class)->create([
            'import_count' => 5,
        ]);

        $importFile->incrementImportCount();

        $this->assertEquals($importFile->import_count, 6);
    }

    /**
     * Test expected behavior for incrementSkipCount function.
     *
     * @return void
     */
    public function testIncrementSkipCount()
    {
        $importFile = factory(ImportFile::class)->create([
            'skip_count' => 2,
        ]);

        $importFile->incrementSkipCount();

        $this->assertEquals($importFile->skip_count, 3);
    }
}
