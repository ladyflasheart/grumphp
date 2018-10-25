# File size

The file size task ensures a maximum size for a file to be added to git.

```yaml
# grumphp.yml
parameters:
    tasks:
        file_name:
            checks:
                -
                     paths_pattern: "/^spec\/Collection\/.+/"
                     rule_name: "Files within the Collection folder must end with 'Collection.php'"
                     rule_pattern: "/^[A-Z a-z]+Collection.php$/"
                      
```

**checks**

*Default: []*

Defines the filename checks. Checks is an array containing further arrays for each check with named keys and values. See below for an explanation of the keys in the arrays within the checks array.

*Following keys are within each array contained in the checks array*

**paths_pattern**

Regex or glob pattern describing the path to which the individual filename check applies. For example `/^spec\/Collection\/.+/` specifies that the filename rule applies to files where the file path starts with the spec directory then the Collection subdirectory (and all subdirectories of Collection directory).

**rule_name**

Human readable string describing the filename rule being checked. Used in the task output. For example : "Files within the Collection folder must end with 'Collection.php'"

**rule_pattern**

Regex or glob pattern describing the file name pattern which the files must conform to. For example `/^[A-Z a-z]+Collection.php$/` enforces the rule that files within the Collection folder must end with 'Collection.php'.
