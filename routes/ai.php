<?php

use App\Mcp\Servers\PublicServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/demo', PublicServer::class);
