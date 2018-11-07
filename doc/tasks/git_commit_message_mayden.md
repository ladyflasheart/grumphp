# Git commit message mayden

The Git commit message task ensures commit messages follow the standards 
set out in https://chris.beams.io/posts/git-commit/. You can turn individual 
aspects on and off or set different widths.

```yaml
# grumphp.yml
parameters:
    tasks:
        git_commit_message_mayden:
            allow_empty_message: false
            enforce_capitalized_subject: true
            enforce_no_subject_trailing_period: true
            enforce_single_lined_subject: true
            max_body_width: 72
            max_subject_width: 50
```

**allow_empty_message**

*Default: false*

Controls whether or not empty commit messages are allowed.

**enforce_capitalized_subject**

*Default: true*

Ensures that the commit message subject line starts with a capital letter.

**enforce_no_subject_trailing_period**

*Default: true*

Ensures that the commit message subject line doesn't have a trailing period.

**enforce_single_lined_subject**

*Default: true*

Ensures that the commit message subject line is followed by a blank line.

**max_body_width**

*Default: 72*

Preferred limit on the commit message body lines. Set to 0 to disable the check.

**max_subject_width**

*Default: 50*

Preferred limit on the commit message subject line. Set to 0 to disable the check.
