<?php

namespace GrumPHP\Task\Git;

use GrumPHP\Configuration\GrumPHP;
use GrumPHP\Runner\TaskResult;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitCommitMsgContext;
use GrumPHP\Task\TaskInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Git CommitMessage Task - Adapted to fit Mayden needs
 */
class CommitMessageMayden implements TaskInterface
{
    /**
     * @var GrumPHP
     */
    private $grumPHP;

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
        return 'git_commit_message_mayden';
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
            'allow_empty_message' => false,
            'enforce_capitalized_subject' => true,
            'enforce_no_subject_trailing_period' => true,
            'enforce_single_lined_subject' => true,
            'max_body_width' => 72,
            'max_subject_width' => 50,
        ]);

        $resolver->addAllowedTypes('allow_empty_message', ['bool']);
        $resolver->addAllowedTypes('enforce_capitalized_subject', ['bool']);
        $resolver->addAllowedTypes('enforce_no_subject_trailing_period', ['bool']);
        $resolver->addAllowedTypes('enforce_single_lined_subject', ['bool']);
        $resolver->addAllowedTypes('max_body_width', ['int']);
        $resolver->addAllowedTypes('max_subject_width', ['int']);

        return $resolver;
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    public function canRunInContext(ContextInterface $context)
    {
        return $context instanceof GitCommitMsgContext;
    }

    /**
     * @param ContextInterface|GitCommitMsgContext $context
     *
     * @return TaskResult
     */
    public function run(ContextInterface $context)
    {
        $config = $this->getConfiguration();
        $errors = [];

        //if message empty don't bother with other checks
        if ((bool) $config['allow_empty_message']) {
            if ($this->commitMessageIsEmpty($context)) {
                return TaskResult::createPassed($this, $context);
            }
        } else {
            if ($this->commitMessageIsEmpty($context)) {
                return TaskResult::createFailed($this, $context, 'Commit message should not be empty.');
            }
        }

        if ((bool) $config['enforce_capitalized_subject'] && !$this->subjectIsCapitalized($context)) {
            $errors[] = 'Subject should start with a capital letter.';
        }

        if ((bool) $config['enforce_single_lined_subject'] && !$this->subjectIsSingleLined($context)) {
            $errors[] = 'Subject should be one line and followed by a blank line.';
        }

        if ((bool) $config['enforce_no_subject_trailing_period'] && $this->subjectHasTrailingPeriod($context)) {
            $errors[] = 'Please omit trailing period from commit message subject.';
        }

        if ($config['max_subject_width'] > 0 && !$this->subjectWidthAcceptable($context)) {
            $errors[] = sprintf('Please keep the subject <= %u characters.', $config['max_subject_width']);
        }

        if ($config['max_body_width'] > 0) {
            $longBodyLines = $this->getTooLongBodyLineNumbers($context);
            if (count($longBodyLines)) {
                $errors[] = sprintf(
                    'The following lines exceeded the maximum width of %u : ',
                    $config['max_body_width']
                )
                . implode(',', $longBodyLines);
            }

        }

        if (count($errors)) {
            return TaskResult::createFailed($this, $context, $this->createAllErrorsMessage($errors));
        }

        return TaskResult::createPassed($this, $context);
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    private function commitMessageIsEmpty(ContextInterface $context)
    {
        $commitMessage = $context->getCommitMessage();
        $lines = $this->getCommitMessageLinesWithoutComments(trim($commitMessage));

        if (count($lines) === 1 && $lines[0] === '') {
            return true;
        }

        return false;
    }

    /**
     * @param array $strings
     *
     * @return string
     */
    private function createAllErrorsMessage(array $strings)
    {
        array_unshift(
            $strings,
            'Run the following command at the repository root to edit '
            . 'your commit message and correct the errors below '
            . '"git commit --edit --file=.git/COMMIT_EDITMSG"'
        );

        return implode(PHP_EOL, $strings);
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    private function subjectWidthAcceptable(ContextInterface $context)
    {
        $subject = rtrim($this->getSubjectLine($context));
        $config = $this->getConfiguration();

        //Allow longer message if it is a generated merge commit
        if ($subject === '' || $this->isMergeCommitMessage($subject)) {
            return true;
        }

        //adjust length limit to allow for rebasing fixup or squash at start
        $maxSubjectWidth = $config['max_subject_width'] + $this->getSpecialPrefixLength($subject);

        if (mb_strlen($subject) > $maxSubjectWidth) {
            return false;
        }

        return true;
    }

    /**
     * @param ContextInterface $context
     *
     * @return array
     */
    private function getTooLongBodyLineNumbers(ContextInterface $context)
    {
        $commitMessage = $context->getCommitMessage();
        $config = $this->getConfiguration();
        $longLines = [];
        $lines = $this->getCommitMessageLinesWithoutComments($commitMessage);

        foreach (array_slice($lines, 2) as $index => $line) {
            if (mb_strlen(rtrim($line)) > $config['max_body_width']) {
                $longLines[] = $index + 3;
            }
        }

        return $longLines;
    }

    /**
     * @param string $string
     *
     * @return int
     */
    private function getSpecialPrefixLength($string)
    {
        if (preg_match('/^(fixup|squash)! /', $string, $match) !== 1) {
            return 0;
        }

        return mb_strlen($match[0]);
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    private function isMergeCommitMessage($string)
    {
        if (preg_match('/^Merge/', $string) === 1) {
            return true;
        }

        return false;
    }


    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    private function subjectHasTrailingPeriod(ContextInterface $context)
    {
        $subjectLine = $this->getSubjectLine($context);

        if (trim($subjectLine) === '') {
            return false;
        }

        if (mb_substr(rtrim($subjectLine), -1) !== '.') {
            return false;
        }

        return true;
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    private function subjectIsCapitalized(ContextInterface $context)
    {
        $commitMessage = $context->getCommitMessage();

        if (trim($commitMessage) === '') {
            return true;
        }

        $lines = $this->getCommitMessageLinesWithoutComments($commitMessage);
        $subject = array_reduce($lines, function ($subject, $line) {
            if ($subject !== null) {
                return $subject;
            }

            if (trim($line) === '') {
                return null;
            }

            return $line;
        }, null);


        if ($subject === null || preg_match('/^[[:punct:]]*(.)/u', $subject, $match) !== 1) {
            return false;
        }

        $firstLetter = $match[1];

        if (preg_match('/^(fixup|squash)!/u', $subject) !== 1 && preg_match('/[[:upper:]]/u', $firstLetter) !== 1) {
            return false;
        }

        return true;
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    private function subjectIsSingleLined(ContextInterface $context)
    {
        $commitMessage = $context->getCommitMessage();

        if (trim($commitMessage) === '') {
            return true;
        }

        $lines = $this->getCommitMessageLinesWithoutComments($commitMessage);

        if (array_key_exists(1, $lines) && trim($lines[1]) !== '') {
            return false;
        }

        return true;
    }

    /**
     * @param string $commitMessage
     *
     * @return array
     */
    private function getCommitMessageLinesWithoutComments($commitMessage)
    {
        $lines = preg_split('/\R/u', $commitMessage);

        return array_values(array_filter($lines, function ($line) {
            return strpos($line, '#') !== 0;
        }));
    }

    /**
     * Gets a clean subject line from the commit message
     *
     * @param $context
     * @return string
     */
    private function getSubjectLine($context)
    {
        $commitMessage = $context->getCommitMessage();
        $lines = $this->getCommitMessageLinesWithoutComments($commitMessage);
        return (string) $lines[0];
    }
}
