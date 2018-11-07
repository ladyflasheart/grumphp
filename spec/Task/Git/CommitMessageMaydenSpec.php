<?php

namespace spec\GrumPHP\Task\Git;

use GrumPHP\Configuration\GrumPHP;
use GrumPHP\Runner\TaskResultInterface;
use GrumPHP\Task\Context\GitCommitMsgContext;
use GrumPHP\Task\Git\CommitMessageMayden;
use GrumPHP\Task\TaskInterface;
use PhpSpec\ObjectBehavior;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommitMessageMaydenSpec extends ObjectBehavior
{
    function let(GrumPHP $grumPHP)
    {
        $this->beConstructedWith($grumPHP);
    }

    function it_should_have_a_name()
    {
        $this->getName()->shouldBe('git_commit_message_mayden');
    }

    function it_should_have_configurable_options()
    {
        $options = $this->getConfigurableOptions();
        $options->shouldBeAnInstanceOf(OptionsResolver::class);
        $options->getDefinedOptions()->shouldContain('allow_empty_message');
        $options->getDefinedOptions()->shouldContain('enforce_capitalized_subject');
        $options->getDefinedOptions()->shouldContain('enforce_no_subject_trailing_period');
        $options->getDefinedOptions()->shouldContain('max_body_width');
        $options->getDefinedOptions()->shouldContain('max_subject_width');
        $options->getDefinedOptions()->shouldContain('enforce_single_lined_subject');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(CommitMessageMayden::class);
    }

    function it_is_a_grumphp_task()
    {
        $this->shouldImplement(TaskInterface::class);
    }

    function it_should_run_in_git_commit_msg_context(GitCommitMsgContext $context)
    {
        $this->canRunInContext($context)->shouldReturn(true);
    }

    function it_runs_the_suite(GrumPHP $grumPHP, GitCommitMsgContext $context)
    {
        $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
            'allow_empty_message'                => true,
            'enforce_capitalized_subject'        => false,
            'enforce_no_subject_trailing_period' => false,
            'enforce_single_lined_subject'       => false,
            'max_body_width'                     => 0,
            'max_subject_width'                  => 0,
        ]);

        $context->getCommitMessage()->willReturn('test');

        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->isPassed()->shouldBe(true);
    }

    function it_should_pass_when_commit_message_is_empty_and_empty_allowed(
        GrumPHP $grumPHP,
        GitCommitMsgContext $context
    ) {
        $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
            'allow_empty_message'                => true,
            'enforce_capitalized_subject'        => true,
            'enforce_no_subject_trailing_period' => true,
            'enforce_single_lined_subject'       => true,
            'max_body_width'                     => 72,
            'max_subject_width'                  => 50,
        ]);

        $context->getCommitMessage()->willReturn('');

        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->isPassed()->shouldBe(true);
    }

    function it_should_fail_when_commit_message_is_empty_and_empty_not_allowed(
        GrumPHP $grumPHP,
        GitCommitMsgContext $context
    ) {
        $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
            'allow_empty_message'                => false,
            'enforce_capitalized_subject'        => true,
            'enforce_no_subject_trailing_period' => true,
            'enforce_single_lined_subject'       => true,
            'max_body_width'                     => 72,
            'max_subject_width'                  => 50,
        ]);

        $context->getCommitMessage()->willReturn('');

        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->isPassed()->shouldBe(false);
        $result->getMessage()->shouldBe('Commit message should not be empty.');
    }

    function it_should_fail_when_commit_message_contains_only_whitespace(GrumPHP $grumPHP, GitCommitMsgContext $context)
    {
        $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
            'allow_empty_message' => false,
            'enforce_capitalized_subject' => false,
            'enforce_no_subject_trailing_period' => false,
            'enforce_single_lined_subject' => false,
            'max_body_width'                     => 0,
            'max_subject_width'                  => 0,
        ]);

        $context->getCommitMessage()->willReturn(' ');

        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->isPassed()->shouldBe(false);
        $result->getMessage()->shouldBe('Commit message should not be empty.');
    }

    function it_should_pass_when_subject_starts_with_a_capital_letter(GrumPHP $grumPHP, GitCommitMsgContext $context)
    {
        $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
            'allow_empty_message' => true,
            'enforce_capitalized_subject' => true,
            'enforce_no_subject_trailing_period' => false,
            'enforce_single_lined_subject'       => false,
            'max_body_width'                     => 0,
            'max_subject_width'                  => 0,
        ]);

            $commitMessage = <<<'MSG'
Initial commit

Mostly cute kitten pictures so far.
MSG;

        $context->getCommitMessage()->willReturn($commitMessage);
        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->isPassed()->shouldBe(true);
    }

    function it_should_pass_when_subject_does_not_start_with_a_capital_letter_but_capital_enforcement_off(GrumPHP $grumPHP, GitCommitMsgContext $context)
    {
        $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
            'allow_empty_message' => true,
            'enforce_capitalized_subject' => false,
            'enforce_no_subject_trailing_period' => false,
            'enforce_single_lined_subject'       => false,
            'max_body_width'                     => 0,
            'max_subject_width'                  => 0,
        ]);

            $commitMessage = <<<'MSG'
initial commit

Mostly cute kitten pictures so far.
MSG;

        $context->getCommitMessage()->willReturn($commitMessage);
        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->isPassed()->shouldBe(true);
    }

    function it_should_fail_when_subject_does_not_start_with_a_capital_letter_and_enforcement_on(GrumPHP $grumPHP, GitCommitMsgContext $context)
    {
        $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
            'allow_empty_message' => false,
            'enforce_capitalized_subject' => true,
            'enforce_no_subject_trailing_period' => false,
            'enforce_single_lined_subject'       => false,
            'max_body_width'                     => 0,
            'max_subject_width'                  => 0,
        ]);

            $commitMessage = <<<'MSG'
initial commit

Mostly cute kitten pictures so far.
MSG;

        $context->getCommitMessage()->willReturn($commitMessage);
        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->isPassed()->shouldBe(false);
        $result->getMessage()->shouldEndWith('Subject should start with a capital letter.');
    }

    function it_should_pass_when_subject_starts_with_a_utf8_capital_letter(GrumPHP $grumPHP, GitCommitMsgContext $context)
    {
        $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
            'allow_empty_message' => true,
            'enforce_capitalized_subject' => true,
            'enforce_no_subject_trailing_period' => false,
            'enforce_single_lined_subject'       => false,
            'max_body_width'                     => 0,
            'max_subject_width'                  => 0,
        ]);

        $commitMessage = <<<'MSG'
Årsgång

Mostly cats so far.
MSG;

        $context->getCommitMessage()->willReturn($commitMessage);

        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->isPassed()->shouldBe(true);
    }

    function it_should_pass_when_subject_starts_with_punctuation_and_a_capital_letter(GrumPHP $grumPHP, GitCommitMsgContext $context)
    {
        $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
            'allow_empty_message' => true,
            'enforce_capitalized_subject' => true,
            'enforce_no_subject_trailing_period' => false,
            'enforce_single_lined_subject'       => false,
            'max_body_width'                     => 0,
            'max_subject_width'                  => 0,
        ]);

        $commitMessage = <<<'MSG'
"Initial" commit

Mostly cats so far.
MSG;

        $context->getCommitMessage()->willReturn($commitMessage);

        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->isPassed()->shouldBe(true);
    }

    function it_should_fail_when_subject_starts_with_a_utf8_lowercase_letter(GrumPHP $grumPHP, GitCommitMsgContext $context)
    {
        $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
            'allow_empty_message' => true,
            'enforce_capitalized_subject' => true,
            'enforce_no_subject_trailing_period' => false,
            'enforce_single_lined_subject'       => false,
            'max_body_width'                     => 0,
            'max_subject_width'                  => 0,
        ]);

        $commitMessage = <<<'MSG'
årsgång

I forget about commit message standards and decide to not capitalize my
subject. Still mostly cats so far.
MSG;

        $context->getCommitMessage()->willReturn($commitMessage);

        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->isPassed()->shouldBe(false);
        $result->getMessage()->shouldEndWith('Subject should start with a capital letter.');
    }

    function it_should_fail_when_subject_starts_with_punctuation_and_a_lowercase_letter(GrumPHP $grumPHP, GitCommitMsgContext $context)
    {
        $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
            'allow_empty_message' => true,
            'enforce_capitalized_subject' => true,
            'enforce_no_subject_trailing_period' => false,
            'enforce_single_lined_subject'       => false,
            'max_body_width'                     => 0,
            'max_subject_width'                  => 0,
        ]);

            $commitMessage = <<<'MSG'
"initial" commit
    
I forget about commit message standards and decide to not capitalize my
subject. Still mostly cats so far.
MSG;

        $context->getCommitMessage()->willReturn($commitMessage);

        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->isPassed()->shouldBe(false);
        $result->getMessage()->shouldEndWith('Subject should start with a capital letter.');
    }

    function it_should_pass_when_subject_starts_with_special_fixup_prefix(GrumPHP $grumPHP, GitCommitMsgContext $context)
    {
        $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
            'allow_empty_message' => true,
            'enforce_capitalized_subject' => true,
            'enforce_no_subject_trailing_period' => false,
            'enforce_single_lined_subject'       => false,
            'max_body_width'                     => 0,
            'max_subject_width'                  => 0,
        ]);

        $commitMessage = <<<'MSG'
fixup! commit

This was created by running git commit --fixup=...
MSG;

        $context->getCommitMessage()->willReturn($commitMessage);

        $result = $this->run($context);
        $result->shouldBeAnInstanceOf(TaskResultInterface::class);
        $result->isPassed()->shouldBe(true);
    }

    function it_should_pass_when_subject_starts_with_special_squash_prefix(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => true,
                'enforce_capitalized_subject' => true,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => false,
                'max_body_width'                     => 0,
                'max_subject_width'                  => 0,
            ]);

            $commitMessage = <<<'MSG'
squash! commit
    
This was created by running git commit --squash=...
MSG;

            $context->getCommitMessage()->willReturn($commitMessage);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(true);
        }

        function it_should_pass_when_first_line_of_commit_message_is_an_empty_line(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => true,
                'enforce_capitalized_subject' => true,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => false,
                'max_body_width'                     => 0,
                'max_subject_width'                  => 0,
            ]);

            $commitMessage = <<<'MSG'

There was no first line

This is a mistake.
MSG;

            $context->getCommitMessage()->willReturn($commitMessage);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(true);
        }

        function it_should_pass_when_commit_message_starts_with_a_comment(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => false,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => false,
                'max_body_width'                     => 0,
                'max_subject_width'                  => 0,
            ]);

            $commitMessage = <<<'MSG'
# Starts with a comment
    
Another reasonable line.
MSG;
            $context->getCommitMessage()->willReturn($commitMessage);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(true);
        }

        function it_should_pass_when_subject_is_separated_from_body_by_a_blank_line(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => false,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => true,
                'max_body_width' => 0,
                'max_subject_width' => 0,
            ]);

            $commitMessage = <<<'MSG'
Initial commit
    
Mostly kitten pictures so far.
MSG;

            $context->getCommitMessage()->willReturn($commitMessage);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(true);
        }

        function it_should_fail_when_subject_is_not_kept_to_one_line(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => false,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => true,
                'max_body_width' => 0,
                'max_subject_width' => 0,
            ]);

            $commitMessage = <<<'MSG'
Initial commit where I forget about commit message
standards and decide to hard-wrap my subject
    
Still mostly cats so far.
MSG;

            $context->getCommitMessage()->willReturn($commitMessage);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(false);
            $result->getMessage()->shouldEndWith('Subject should be one line and followed by a blank line.');
        }

        function it_should_fail_when_subject_is_longer_than_50_characters(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $context->getCommitMessage()->willReturn(str_repeat('A', 51));
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => true,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => false,
                'max_body_width' => 0,
                'max_subject_width' => 50,
            ]);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(false);
            $result->getMessage()->shouldEndWith('Please keep the subject <= 50 characters.');
        }

        function it_should_pass_when_subject_is_50_characters_or_fewer(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $context->getCommitMessage()->willReturn(str_repeat('A', 50));
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => true,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => false,
                'max_body_width' => 0,
                'max_subject_width' => 50,
            ]);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(true);
        }

        function it_should_pass_when_subject_starts_with_special_fixup_and_is_longer_than_50_characters(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $context->getCommitMessage()->willReturn('fixup! '.str_repeat('A', 50));
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => true,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => false,
                'max_body_width' => 0,
                'max_subject_width' => 50,
            ]);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(true);
        }

        function it_should_pass_when_subject_starts_with_special_squash_and_is_longer_than_50_characters(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $context->getCommitMessage()->willReturn('squash! '.str_repeat('A', 50));
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => true,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => false,
                'max_body_width' => 0,
                'max_subject_width' => 50,
            ]);
            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(true);
        }

        function it_should_pass_when_the_subject_is_50_characters_followed_by_a_newline(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => true,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => false,
                'max_body_width' => 0,
                'max_subject_width' => 50,
            ]);

            $commitMessage = <<<'MSG'
This is 50 characters long exactly, plus a newline
    
A reasonable line.
MSG;

            $context->getCommitMessage()->willReturn($commitMessage);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(true);
        }

        function it_should_pass_when_a_line_in_the_message_is_72_characters_followed_by_a_newline(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => true,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => false,
                'max_body_width' => 72,
                'max_subject_width' => 0,
            ]);
            $commitMessage = <<<'MSG'
Some summary
    
This line has 72 characters, but with newline it has 73 characters
That shouldn't be a problem.
MSG;

            $context->getCommitMessage()->willReturn($commitMessage);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(true);
        }

        function it_should_fail_when_a_line_in_the_message_is_longer_than_72_characters(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => true,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => false,
                'max_body_width' => 72,
                'max_subject_width' => 0,
            ]);

            $commitMessage = <<<'MSG'
Some summary
    
This line is longer than 72 characters which you can clearly see if you count the characters.
MSG;

            $context->getCommitMessage()->willReturn($commitMessage);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(false);
            $result->getMessage()->shouldEndWith('The following lines exceed the maximum width of 72 : 3');
        }

        function it_should_pass_when_a_line_in_the_message_is_commented_but_longer_than_72_characters(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => true,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => false,
                'max_body_width' => 72,
                'max_subject_width' => 0,
            ]);

            $commitMessage = <<<'MSG'
Some summary
    
# This line is longer than 72 characters but it is commented out and won't be included in the commit text.
MSG;

            $context->getCommitMessage()->willReturn($commitMessage);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(true);
        }

        function it_should_pass_when_all_lines_in_the_message_are_fewer_than_72_characters(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => true,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => false,
                'max_body_width' => 72,
                'max_subject_width' => 0,
            ]);

            $commitMessage = <<<'MSG'
Some summary
    
A reasonable line.
    
Another reasonable line.
MSG;

            $context->getCommitMessage()->willReturn($commitMessage);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(true);
        }

        function it_should_fail_when_subject_and_a_line_in_the_message_is_longer_than_the_limits(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => true,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => false,
                'enforce_single_lined_subject' => false,
                'max_body_width' => 72,
                'max_subject_width' => 50,
            ]);
            $commitMessage = <<<'MSG'
A subject line that is way too long. A subject line that is way too long.
    
A message line that is way too long. A message line that really is way too long.
MSG;

            $context->getCommitMessage()->willReturn($commitMessage);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(false);
            $result->getMessage()->shouldContain('The following lines exceed the maximum width of 72 : 3');
            $result->getMessage()->shouldContain('Please keep the subject <= 50 characters.');
        }

        function it_should_fail_when_subject_contains_a_trailing_period(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => true,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => true,
                'enforce_single_lined_subject' => false,
                'max_body_width' => 0,
                'max_subject_width' => 0,
            ]);

            $context->getCommitMessage()->willReturn('This subject has a period.');

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(false);
            $result->getMessage()->shouldEndWith('Please omit trailing period from commit message subject.');
        }

        function it_should_pass_when_subject_does_not_contain_a_trailing_period(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => true,
                'enforce_capitalized_subject' => false,
                'enforce_no_subject_trailing_period' => true,
                'enforce_single_lined_subject' => false,
                'max_body_width' => 0,
                'max_subject_width' => 0,
            ]);

            $context->getCommitMessage()->willReturn('This subject has no period');

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(true);
        }

        function it_should_give_all_errors_if_all_checks_fail_except_empty_subject(GrumPHP $grumPHP, GitCommitMsgContext $context)
        {
            $grumPHP->getTaskConfiguration('git_commit_message_mayden')->willReturn([
                'allow_empty_message' => false,
                'enforce_capitalized_subject' => true,
                'enforce_no_subject_trailing_period' => true,
                'enforce_single_lined_subject' => true,
                'max_body_width' => 72,
                'max_subject_width' => 50,
            ]);

            $commitMessage = <<<'MSG'
a subject line that is way too long. A subject line that is way too long.
Also a second line that should be blank and is not.    
A message line that is way too long. A message line that really is way too long.
MSG;
            $context->getCommitMessage()->willReturn($commitMessage);

            $result = $this->run($context);
            $result->shouldBeAnInstanceOf(TaskResultInterface::class);
            $result->isPassed()->shouldBe(false);
            $result->getMessage()->shouldContain('Subject should start with a capital letter.');
            $result->getMessage()->shouldContain('Subject should be one line and followed by a blank line.');
            $result->getMessage()->shouldContain('Please omit trailing period from commit message subject.');
            $result->getMessage()->shouldContain('The following lines exceed the maximum width of 72 : 3');
            $result->getMessage()->shouldContain('Please keep the subject <= 50 characters.');
        }
}
