<?php

namespace Bolt\Composer\Action;

use Bolt\Composer\EventListener\PackageEventListener;
use Bolt\Exception\PackageManagerException;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Request;
use Composer\Factory;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Package\PackageInterface;
use Composer\Repository\CompositeRepository;

/**
 * Composer script runner.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RunScripts extends BaseAction
{
    /**
     * Entry point for running scripts.
     *
     * @param string $eventName
     * @param bool   $root
     *
     * @throws PackageManagerException
     */
    public function execute($eventName, $root = false)
    {
        try {
            $this->runScript($eventName, $root);
        } catch (PackageManagerException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Attempt to run a Composer script.
     *
     * @param string $eventName
     * @param bool   $root
     *
     * @throws PackageManagerException
     */
    protected function runScript($eventName, $root = false)
    {
        if ($eventName === PackageEvents::POST_PACKAGE_INSTALL) {
            $this->runPostPackageInstall($root);
        } else {
            throw new PackageManagerException(sprintf('The event named "%s" is not supported.', $eventName));
        }
    }

    /**
     * Run Composer's PackageEvents::POST_PACKAGE_INSTALL script(s).
     *
     * @param bool $root
     */
    protected function runPostPackageInstall($root = false)
    {
        $io = $this->getIO();
        if ($root) {
            $composerJson = $this->app['resources']->getPath('root/composer.json');
            $composer = Factory::create($io, $composerJson, true);
        } else {
            $composer = $this->getComposer();
        }
        $repo = $composer->getRepositoryManager()->getLocalRepository();
        /** @var $package \Composer\Package\PackageInterface */
        foreach ($repo->getPackages() as $package) {
            $this->getIO()->write(sprintf('    - %s', $package->getName()));
            $event = $this->getPackageEvent($package, PackageEvents::POST_PACKAGE_INSTALL);
            PackageEventListener::handle($event);
        }
    }

    /**
     * Build a Composer PackageEvent object.
     *
     * @param PackageInterface $package
     * @param string           $eventName
     *
     * @return PackageEvent
     */
    protected function getPackageEvent(PackageInterface $package, $eventName)
    {
        $operation = new InstallOperation($package);
        $policy = new DefaultPolicy($this->getOptions()->preferStable(), $this->getOptions()->preferLowest());

        return new PackageEvent(
            $eventName,
            $this->getComposer(),
            $this->getIO(),
            $this->getOptions()->noDev(),
            $policy,
            $this->getPool(),
            new CompositeRepository($this->getRepos()),
            new Request(),
            [$operation],
            $operation
        );
    }
}
