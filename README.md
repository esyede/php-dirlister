# php-dirlister

Simple directory lister in PHP (Single file, no dependencies)

![screenshot](https://user-images.githubusercontent.com/10582583/273416139-fa4133da-508b-48ac-9bc5-e60956371e80.png)


## Requirements:

  - PHP 5.3 or newer
  - `pathinfo()`


## Install

Just download the latest release and upload the `index.php` file to your server's subdirectory. That's it!


## Configuration

You can modify the default configuration inside the `index.php` file:

```php
/*
|--------------------------------------------------------------------------
| CONFIGURATION
|--------------------------------------------------------------------------
|
| Modify this to suits your
|
*/

$config = array(
    'page_title' => 'Index of [path]',
    'page_subtitle' => 'Total: [items] items, [size]',
    'browse_directories' => true,
    'show_breadcrumbs' => true,
    'show_directories' => true,
    'show_footer' => true,
    'show_parent' => false,
    'show_hidden' => false,
    'directory_first' => true,
    'content_alignment' => 'center',
    'date_format' => 'd M Y H:i',
    'timezone' => 'Asia/Jakarta',
    'ignore_list' => array(
        '.DS_Store',
        '.git',
        '.gitmodules',
        '.gitignore',
        '.vscode',
        'vendor',
        'node_modules',
    ),
);

```


## License

Released under the [MIT License](https://github.com/esyede/php-dirlister/LICENSE)
