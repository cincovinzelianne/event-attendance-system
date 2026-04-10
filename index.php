<?php
/**
 * Workspace root entrypoint.
 * Forward traffic to the Laravel web app public bridge.
 */
header('Location: /public/');
exit;
