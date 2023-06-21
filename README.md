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

### Purging Inactive User Accounts

In the initial version of the plugin, inactive user accounts will be purged based on the following criteria:

- User accounts registered more than six months ago with no activity since registration.

Please note that this purging process will be triggered only once during the installation of the plugin to remove existing inactive user accounts. After that, the plugin will continue to manage inactive accounts based on the configured inactivity duration.

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