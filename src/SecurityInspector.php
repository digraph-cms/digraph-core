<?php

namespace DigraphCMS;

use Joby\Smol\Sentry\InspectionRules\InspectionRule;
use Joby\Smol\Sentry\InspectionRules\RequestData;
use Joby\Smol\Sentry\Severity;

/**
 * Particularly aggressive check for potential glob attacks
 */
class SecurityInspector implements InspectionRule
{

    /**
     * @inheritDoc
     */
    public function check(RequestData $request): Severity|null
    {
        if (
            preg_match(
                '/[\*\?\[\]\{\}]|\.\.|\%(?:2e|2f|5c|00)|[\x00-\x1f\x7f]/i',
                $request->pathString(true),
            )
        )
            return Severity::Malicious;
        else
            return null;
    }

}
