# Inactive User Account Manager Plugin for Moodle

The Inactive User Account Manager plugin is a powerful tool for Moodle administrators to manage inactive user accounts while preserving user-generated content. This plugin automates the process of identifying and deleting inactive accounts, ensuring a clean and active user base, while still retaining the associated content.

## Key Features

- **Account Inactivity Management**: Define criteria for identifying inactive user accounts based on specified periods of inactivity.
- **Suspension and Notification**: Automatically suspend inactive accounts and notify users about the impending deletion, giving them a chance to reactivate their accounts.
- **Content Preservation**: Intelligently preserve user-generated content associated with inactive user accounts, including forum posts, course materials, assignments, and more.
- **Transparent Deletion Process**: Log all activities related to account deletions, providing administrators with an audit trail of the deletion process.
- **Configurable Settings**: Easily configure inactivity duration, suspension periods, and other settings to align with your Moodle instance's requirements.

## Getting Started

### Requirements

- Moodle version 4.2.0 or higher

### Installation

1. Clone this repository or download the ZIP archive in the `admin/tool` folder.
2. Log in to your Moodle site as an administrator.
3. Go to "Site administration" > "Notifications" and follow the on-screen instructions to complete the plugin installation.
4. Configure the plugin settings according to your requirements.

### Purging Deleted User Records (Command Line Script)

To further manage and clean up the database by purging records of users already deleted, you can utilize a command line script provided by the Inactive User Account Manager plugin. This script manually purges the records of deleted users, ensuring they do not have any associated content and provides logging and backup functionality. Follow the instructions below to use the script:

1. Ensure you have access to the command line interface (CLI) of your Moodle server.
2. Locate the command line script named `purgedeletedusers.php` in the plugin's installation directory (*admin/tool/purgeusers/cli/purgedeletedusers.php*).
3. Execute the script using the following command:
```shell
$ php admin/tool/purgeusers/cli/purgedeletedusers.php
```
4. The script will scan the database for deleted user records and perform the following actions for each record:
- Check if the deleted user has any associated content, such as forum posts or assignments.
- If the user has associated content, the script will log this information and skip the deletion process.
- If the user does not have any associated content, the script will permanently purge the user's record from the database.
5. If logging and backup are enabled, the script will, by default, generate a log file that provides details of the actions performed. This includes information on which records were purged and which ones were skipped due to associated content. Additionally, a copy of each deleted record will be saved for future recovery.
6. It is recommended to periodically review the generated log file to ensure the purging process is running as expected and to monitor any skipped records that may require manual intervention.

The components to check for activity are defined in code as an array in the manager class (*admin/tool/purgeusers/classes/manager.php*). In a next release, we intend to add this feature as a setting.

**Note**: The `purgedeletedusers.php` script should be run manually as needed. It is advisable to schedule regular executions of the script, depending on your Moodle instance's requirements, to keep the database clean and optimized.

### Purging Inactive User Accounts

We plan to develop this further in the next stage.

## Documentation

For detailed information on installing, configuring, and using the Inactive User Account Manager plugin, please refer to the [official documentation](link_to_documentation).

## Contributing

We welcome contributions to the Inactive User Account Manager plugin! If you have any bug fixes, feature requests, or improvements, please submit them as pull requests. Make sure to read our [contribution guidelines](link_to_contribution_guidelines) before getting started.

## License

The Inactive User Account Manager plugin is released under the [MIT License](link_to_license). Please see the `LICENSE` file for more details.

## Support

For any questions, issues, or feedback related to the Inactive User Account Manager plugin, please [open an issue](link_to_issues) on GitHub. We'll be happy to assist you!

---

Implementing the Inactive User Account Manager plugin allows you to effectively manage inactive user accounts in your Moodle instance while preserving user-generated content. This plugin ensures a streamlined user experience, better data hygiene, and improved system performance.

We appreciate your interest and hope you find the Inactive User Account Manager plugin beneficial for your Moodle site. Thank you for using our plugin!