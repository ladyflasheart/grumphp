<?php

namespace GrumPHP\Task;

use GrumPHP\Configuration\GrumPHP;
use GrumPHP\Runner\TaskResult;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Exception\RuntimeException;
use GrumPHP\Task\Context\RunContext;
use GrumPHP\Util\Regex;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileName implements TaskInterface
{
    /**
     * @var GrumPHP
     */
    protected $grumPHP;

    /**
     * @param GrumPHP $grumPHP
     */
    public function __construct(GrumPHP $grumPHP)
    {
        $this->grumPHP = $grumPHP;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'file_name';
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        $configured = $this->grumPHP->getTaskConfiguration($this->getName());

        return $this->getConfigurableOptions()->resolve($configured);
    }

    /**
     * @return OptionsResolver
     */
    public function getConfigurableOptions()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'checks' => [],
        ]);

        $resolver->addAllowedTypes('checks', 'array');

        return $resolver;
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    public function canRunInContext(ContextInterface $context)
    {
        return $context instanceof RunContext || $context instanceof GitPreCommitContext;
    }

    /**
     * @param ContextInterface|RunContext $context
     *
     * @return TaskResult
     */
    public function run(ContextInterface $context)
    {
        $config = $this->getConfiguration();

        if (0 === $context->getFiles()->count()) {
            return TaskResult::createSkipped($this, $context);
        }

        $exceptions = [];
        foreach ($config['checks'] as $filenameCheck) {
            $files = $context->getFiles()->path($filenameCheck['paths_pattern']);

            if (0 !== $files->count()) {
                foreach ($files as $file) {
                    $info = new \SplFileInfo($file);
                    try {
                        $this->runMatcher(
                            $info->getFilename(),
                            $filenameCheck['rule_pattern']
                        );
                    } catch (RuntimeException $e) {
                        $exceptions[$filenameCheck['rule_name']][] = $e->getMessage();
                    }
                }
            }
        }

        if (count($exceptions)) {
            $errorMessages = [];
            foreach ($exceptions as $rule => $badFiles) {
                $errorMessages[] = "Rule : $rule breached by files : " . implode(', ', $badFiles);
            }
            return TaskResult::createFailed(
                $this,
                $context,
                implode(PHP_EOL, $errorMessages)
            );
        }

        return TaskResult::createPassed($this, $context);
    }

    /**
     * @param string $fileName
     * @param string $rule
     *
     * @throws RuntimeException
     */
    private function runMatcher($fileName, $rule)
    {
        $regex = new Regex($rule);

        if (!preg_match((string) $regex, $fileName)) {
            throw new RuntimeException("$fileName");
        }
    }
}
