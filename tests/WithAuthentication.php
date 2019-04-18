<?php

namespace Tests;

trait WithAuthentication
{
    /**
     * Set the Chompy API key on the request.
     *
     * @return $this
     */
    public function withChompyApiKey()
    {
        $header = $this->transformHeadersToServerVars(['X-DS-Importer-API-Key' => env('IMPORTER_API_KEY')]);

        $this->serverVariables = array_merge($this->serverVariables, $header);

        return $this;
    }
}
