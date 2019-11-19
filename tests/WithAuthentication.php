<?php

namespace Tests;

trait WithAuthentication
{
    /**
     * Set the Chompy API key on the request.
     *
     * @return $this
     */
    public function withCallPowerApiKey()
    {
        $header = $this->transformHeadersToServerVars(['X-DS-CallPower-API-Key' => env('CALLPOWER_API_KEY')]);

        $this->serverVariables = array_merge($this->serverVariables, $header);

        return $this;
    }

    /**
     * Set the Chompy API key on the request.
     *
     * @return $this
     */
    public function withSoftEdgeApiKey()
    {
        $header = $this->transformHeadersToServerVars(['X-DS-SoftEdge-API-Key' => env('SOFTEDGE_API_KEY')]);

        $this->serverVariables = array_merge($this->serverVariables, $header);

        return $this;
    }
}
