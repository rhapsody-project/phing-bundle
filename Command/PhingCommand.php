<?php
/* Copyright (c) 2013 Rhapsody Project
 *
 * Licensed under the MIT License (http://opensource.org/licenses/MIT)
 *
 * Permission is hereby granted, free of charge, to any
 * person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the
 * Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice
 * shall be included in all copies or substantial portions of
 * the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY
 * KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
 * OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR
 * OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT
 * OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace Rhapsody\PhingBundle\Command;

use \RuntimeException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class PhingCommand extends ContainerAwareCommand
{

	const PHING_DEBUG_FLAG = '-debug';
	const PHING_QUIET_FLAG = '-quiet';
	const PHING_VERBOSE_FLAG = '-verbose';

	private $overrideRootDirectory = false;

	/**
	 * @see Command
	 */
	protected function configure()
	{
		$this
		->setName('rhapsody:phing:run')
		->setDefinition(array(
				new InputOption('file', '-f', InputOption::VALUE_OPTIONAL, 'The build file. If not specified, defaults to build.xml'),
				new InputOption('debug', '', InputOption::VALUE_OPTIONAL, 'Whether to run Phing with debug diagnostics turned on.'),
				new InputOption('property', '-D', InputOption::VALUE_OPTIONAL, 'The properties to set.'),
				new InputOption('target', '', InputOption::VALUE_OPTIONAL, 'The target(s) to run. If you want to run more than one target, separate targets by commas. E.g. --target=target1, target2, ...'),
		))
		->setDescription('Runs phing against a build script.')
		->setHelp(<<<EOF
The <info>phing:run</info> command executes a build script and optional target against
the Phing tool for this environment.
EOF
		);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try {
			$args = $this->preparePhingArgs($input, $output);
			$phingClasspath = $this->getContainer()->getParameter('rhapsody_phing.phing_classpath');
			$phingHome = $this->getContainer()->getParameter('rhapsody_phing.phing_home');

			if ($output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
				$commandline = 'phing ' . implode(' ', $args);
				$output->writeln('Executing Phing with: ' . $commandline);
			}

			//require_once('phing/Phing.php');

			\Phing::startup();
			\Phing::setProperty('rhapsody_phing.home', $phingHome);
			\Phing::fire($args);
			\Phing::shutdown();
		}
		catch (ConfigurationException $x) {
			Phing::printMessage($x);
			exit(-1);
		}
		catch (Exception $x) {
			exit(1);
		}
	}

	/**
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	private function preparePhingArgs(InputInterface $input, OutputInterface $output) {
		$args = array();

		$buildFile = $this->getBuildFile($input, $output);
		if (!empty($buildFile)) {
			array_push($args, '-f');
			array_push($args, $buildFile);
		}

		$verbosity = $this->getVerbosity($input, $output);
		if (!empty($verbosity)) {
			array_push($args, $verbosity);
		}

		$debug = $this->isDebug($input, $output);
		if ($debug) {
			array_push($args, PhingCommand::PHING_DEBUG_FLAG);
		}

		$rootDir = $this->getContainer()->getParameter('rhapsody_phing.root_dir');
		array_push($args, '-Dbasedir=' . $rootDir);
		$properties = $this->getProperties($input, $output);
		if (!empty($properties)) {
			if ($output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
				$output->writeln('Adding properties to arguments: ' . $properties);
			}
			$args = array_merge($args, $properties);
		}

		$targets = $this->getTargets($input, $output);
		if (!empty($targets)) {
			$args = array_merge($args, $targets);
		}
		return $args;
	}

	/**
	 *
	 * @param InputInterface $input
	 * @throws RuntimeException
	 */
	private function getBuildFile(InputInterface $input, OutputInterface $output) {
		$file = $input->getOption('file');
		if (empty($file)) {
			$scriptDir = $this->getContainer()->getParameter('rhapsody_phing.root_dir');
			if ($output->getVerbosity() == 1) {
				$output->writeln('No build file specified, setting file to: build.xml');
			}
			$file = 'build.xml';
			$file = $scriptDir . DIRECTORY_SEPARATOR . $file;
		}

		$buildFile = realpath($file);
		if (empty($buildFile)) {
			throw new RuntimeException('Unable to find file: ' . $file . '; Aborting.');
		}
		return $buildFile;
	}

	/**
	 *
	 * @return string
	 */
	private function getPhingPath() {
		$dir = $this->getContainer()->getParameter('rhapsody_phing.phing_home');
		$phing = $this->getContainer()->getParameter('rhapsody_phing.phing');
		return realpath($dir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $phing);
	}

	/**
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	private function getProperties(InputInterface $input, OutputInterface $output) {
		$properties = $input->getOption('property');
		if (!empty($properties)) {
			$properties = explode(',', $properties);
			$props = array();
			foreach ($properties as $property) {
				array_push($props, '-D' . $property);
			}
			return $props;
		}
		return array();
	}

	/**
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	private function getTargets(InputInterface $input, OutputInterface $output) {
		$targets = $input->getOption('target');
		if (!empty($targets)) {
			$targets = explode(',', $targets);
			return $targets;
		}
		return array();
	}

	/**
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	private function getVerbosity(InputInterface $input, OutputInterface $output) {
		$verbosity = $output->getVerbosity();
		if ($verbosity == OutputInterface::VERBOSITY_QUIET) {
			return PhingCommand::PHING_QUIET_FLAG;
		}
		return $verbosity > 1 ? PhingCommand::PHING_VERBOSE_FLAG : '';
	}

	/**
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	private function isDebug(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('debug')) {
			return true;
		}
		return false;
	}
}