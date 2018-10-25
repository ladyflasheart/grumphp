<?php

namespace spec\GrumPHP\Task;

use PhpSpec\ObjectBehavior;
use GrumPHP\Task\FileName;
use GrumPHP\Runner\TaskResult;
use ArrayIterator;
use GrumPHP\Collection\FilesCollection;
use GrumPHP\Configuration\GrumPHP;
use GrumPHP\Process\ProcessBuilder;
use GrumPHP\Runner\TaskResultInterface;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileNameSpec extends ObjectBehavior
{
    function let(GrumPHP $grumPHP)
    {
        $pathsPattern = '/^mysql\/.+/';
        $ruleName = 'Patch files must follow special naming format';
        $rulePattern = '/^20[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|1[0-9]|2[0-9]|3[0-1])(-[a-z0-9]+)+.(sql|sqx)$/';
        $sqlFileCheck =
                  [ 'checks' =>
                        [ 0 => [ 'paths_pattern' => $pathsPattern,
                                 'rule_name' => $ruleName,
                                 'rule_pattern' => $rulePattern,
                               ],
                        ],
                  ];
        $grumPHP->getTaskConfiguration('file_name')->willReturn($sqlFileCheck);
        $this->beConstructedWith($grumPHP);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(FileName::class);
    }

    function it_should_have_a_name()
    {
        $this->getName()->shouldBe('file_name');
    }

    function it_should_have_configurable_options()
    {
        $options = $this->getConfigurableOptions();
        $options->shouldBeAnInstanceOf(OptionsResolver::class);
        $options->getDefinedOptions()->shouldContain('checks');
    }

    function it_should_run_in_git_pre_commit_context(GitPreCommitContext $context)
    {
        $this->canRunInContext($context)->shouldReturn(true);
    }

    function it_should_run_in_run_context(RunContext $context)
    {
        $this->canRunInContext($context)->shouldReturn(true);
    }

    function it_does_not_do_anything_if_there_are_no_files(ProcessBuilder $processBuilder, ContextInterface $context)
    {
        $processBuilder->buildProcess('file_name')->shouldNotBeCalled();
        $processBuilder->buildProcess()->shouldNotBeCalled();
        $context->getFiles()->willReturn(new FilesCollection());

        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->getResultCode()->shouldBe(TaskResult::SKIPPED);
    }

    function it_runs_the_suite(RunContext $context, FilesCollection $filesCollection)
    {
        $filesCollection->path('^/mysql\/.+')->willReturn(
            new FilesCollection(
                [
                    new SplFileInfo(
                        'mysql/patches/20181012-test-patch-file.sql',
                        'mysql/patches',
                        'mysql/patches/20181012-test-patch-file.sql'
                    )
                ]
            )
        );

        $context->getFiles()->willReturn(
            new FilesCollection(
                [
                    new SplFileInfo(
                        'src/Collection/TaskResultCollection.php',
                        'src/Collection',
                        'TaskResultCollection.php'
                    ),
                    new SplFileInfo(
                        'mysql/patches/20181012-test-patch-file.sql',
                        'mysql/patches',
                        'mysql/patches/20181012-test-patch-file.sql'
                    )
                ]
            )
        );

        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->isPassed()->shouldBe(true);
        $result->getResultCode()->shouldBe(TaskResult::PASSED);
    }

    function it_throws_exception_if_the_process_fails(RunContext $context, FilesCollection $filesCollection)
    {
        $patchFiles = [
            new SplFileInfo(
                'mysql/patches/20181012-test-patch-file.sql',
                'mysql/patches',
                'mysql/patches/20181012-test-patch-file.sql'
            ),
            new SplFileInfo(
                'mysql/patches/20181342-test-patch-file.sql',
                'mysql/patches',
                'mysql/patches/20181342-test-patch-file.sql'
            ),
            new SplFileInfo(
                'mysql/patches/20180828-test-patch-file',
                'mysql/patches',
                'mysql/patches/20180828-test-patch-file'
            )
        ];

        $filesCollection->path('^/mysql\/.+')->willReturn(new FilesCollection($patchFiles));

        $allFiles = array_merge(
            [
                new SplFileInfo(
                    'src/Collection/TaskResultCollection.php',
                    'src/Collection',
                    'TaskResultCollection.php'
                ),
            ],
            $patchFiles
        );

        $context->getFiles()->willReturn(new FilesCollection($allFiles));

        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->isPassed()->shouldBe(false);
        $result->getResultCode()->shouldBe(TaskResult::FAILED);
        $result->getMessage()->shouldBe(
            'Rule : Patch files must follow special naming format breached by files : 20181342-test-patch-file.sql, 20180828-test-patch-file'
        );
    }
}
