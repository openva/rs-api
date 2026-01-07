<?php

declare(strict_types=1);

/**
 * Returns a mysqli connection using the shared Database helper.
 */
function api_db(): mysqli
{
    $database = new Database();

    return $database->connect_mysqli();
}

