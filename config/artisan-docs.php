<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Output Format
    |--------------------------------------------------------------------------
    |
    | Supported formats: "markdown", "html", "json"
    |
    */
    'default_format' => env('ARTISAN_DOCS_FORMAT', 'markdown'),

    /*
    |--------------------------------------------------------------------------
    | Default Output Path
    |--------------------------------------------------------------------------
    |
    | The default file path where documentation will be written.
    | Relative paths are resolved from the application base path.
    |
    */
    'default_output' => env('ARTISAN_DOCS_OUTPUT', 'docs/commands.md'),

    /*
    |--------------------------------------------------------------------------
    | Include Hidden Commands
    |--------------------------------------------------------------------------
    |
    | Whether to include commands that are marked as hidden in the output.
    |
    */
    'include_hidden' => false,

    /*
    |--------------------------------------------------------------------------
    | Include Vendor Commands
    |--------------------------------------------------------------------------
    |
    | Whether to include commands from vendor packages. Set to false to show
    | only commands defined in your application's app/ directory.
    |
    */
    'include_vendor' => true,

    /*
    |--------------------------------------------------------------------------
    | Excluded Namespaces
    |--------------------------------------------------------------------------
    |
    | Command namespaces to always exclude from the generated documentation.
    | Example: ['_', 'completion']
    |
    */
    'excluded_namespaces' => [
        '_',
        'completion',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Commands
    |--------------------------------------------------------------------------
    |
    | Specific command names to always exclude from the generated documentation.
    |
    */
    'excluded_commands' => [],

    /*
    |--------------------------------------------------------------------------
    | Custom Grouping Rules
    |--------------------------------------------------------------------------
    |
    | Define custom group labels for command namespaces. By default, commands
    | are grouped by their namespace prefix (e.g. "make:", "queue:").
    | Keys are namespace prefixes, values are the display group names.
    |
    */
    'groups' => [
        'make' => 'Code Generators',
        'db' => 'Database',
        'migrate' => 'Database',
        'queue' => 'Queue',
        'schedule' => 'Scheduler',
        'cache' => 'Cache',
        'config' => 'Configuration',
        'event' => 'Events',
        'key' => 'Security',
        'storage' => 'Storage',
        'view' => 'Views',
        'route' => 'Routing',
        'package' => 'Packages',
        'vendor' => 'Packages',
        'auth' => 'Authentication',
        'horizon' => 'Horizon',
        'telescope' => 'Telescope',
        'nova' => 'Nova',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Command Paths
    |--------------------------------------------------------------------------
    |
    | Paths used to detect whether a command is "custom" (application-defined)
    | versus a vendor/framework command. Used when --only-custom is passed.
    |
    */
    'app_command_paths' => [
        'App\\Console\\Commands\\',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTML Template
    |--------------------------------------------------------------------------
    |
    | The view template used when generating HTML output. You may publish the
    | default template and customise it, then point this setting to your view.
    |
    */
    'html_template' => 'artisan-docs::commands',

    /*
    |--------------------------------------------------------------------------
    | Document Title
    |--------------------------------------------------------------------------
    |
    | The title displayed at the top of the generated documentation.
    |
    */
    'title' => env('ARTISAN_DOCS_TITLE', 'Artisan Command Reference'),

];
