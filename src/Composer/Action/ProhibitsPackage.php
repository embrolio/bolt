<?php

namespace Bolt\Composer\Action;

/**
 * Shows which packages prevent the given package from being installed with
 * detailed information about why a package cannot be installed.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class ProhibitsPackage extends AbstractDependencyAction
{
    /**
     * {@inheritdoc}
     */
    public function execute($packageName, $textConstraint = '*', $onlyLocal = true)
    {
        $this->inverted = true;

        return parent::execute($packageName, $textConstraint);
    }
}
