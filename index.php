<?php

/*
|--------------------------------------------------------------------------
| CONFIGURATION
|--------------------------------------------------------------------------
|
| Modify this to suits your need.
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


/*
|--------------------------------------------------------------------------
| ACTUAL PROGRAM STARTS HERE
|--------------------------------------------------------------------------
*/

class PHPDirLister
{
    private $self;
    private $path;
    private $browse;
    private $total;
    private $totalSize;
    private $config = array();

    public function __construct(array $config = array())
    {
        $this->config = $config;
        $this->self = basename($_SERVER['PHP_SELF']);
        $this->path = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
        $this->total = 0;
        $this->totalSize = 0;

        if ($this->config['browse_directories']) {
            $_GET['b'] = trim(str_replace('\\', '/', (string) isset($_GET['b']) ? $_GET['b'] : ''), '/ ');
            $_GET['b'] = str_replace(array('/..', '../'), '', (string) isset($_GET['b']) ? $_GET['b'] : '');

            if (!empty($_GET['b']) && $_GET['b'] !== '..' && is_dir($_GET['b'])) {
                $ignored = false;
                $names = explode('/', $_GET['b']);

                foreach ($names as $name) {
                    if (in_array($name, $this->config['ignore_list'])) {
                        $ignored = true;
                        break;
                    }
                }

                if (!$ignored) {
                    $this->browse = $_GET['b'];
                }

                if (!empty($this->browse)) {
                    $index = null;

                    if (is_file($this->browse . '/index.htm')) {
                        $index = '/index.htm';
                    } elseif (is_file($this->browse . '/index.html')) {
                        $index = '/index.html';
                    } elseif (is_file($this->browse . '/index.php')) {
                        $index = '/index.php';
                    }

                    if (!is_null($index)) {
                        header('Location: ' . $this->browse . $index);
                        exit;
                    }
                }
            }
        }
    }

    public function getSelf()
    {
        return $this->self;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getBrowse()
    {
        return $this->browse;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function getTotalSize()
    {
        return $this->totalSize;
    }

    public function getConfig($key, $default = null)
    {
        return array_key_exists($key, $this->config) ? $this->config[$key] : $default;
    }

    public function getListings($path)
    {
        $ls = array();
        $lsDir = array();

        if (($dh = @opendir($path)) === false) {
            return $ls;
        }

        $path .= (substr($path, -1) !== '/') ? '/' : '';

        while (($file = readdir($dh)) !== false) {
            if (
                $file === $this->self
                || $file === '.'
                || $file == '..'
                || (!$this->config['show_hidden'] && substr($file, 0, 1) === '.')
                || in_array($file, $this->config['ignore_list'])
            ) {
                continue;
            }

            $isDir = is_dir($path . $file);

            if (!$this->config['show_directories'] && $isDir) {
                continue;
            }

            $item = array(
                'name' => $file,
                'is_dir' => $isDir,
                'size' => $isDir ? 0 : filesize($path . $file),
                'time' => filemtime($path . $file),
            );

            if ($isDir) {
                $lsDir[] = $item;
            } else {
                $ls[] = $item;
            }

            $this->total++;
            $this->totalSize += $item['size'];
        }

        return array_merge($lsDir, $ls);
    }

    public function sortByName($a, $b)
    {
        return (($a['is_dir'] === $b['is_dir'] || !$this->config['directory_first']) ? (strtolower($a['name']) > strtolower($b['name'])) : ($a['is_dir'] < $b['is_dir'])) ? 1 : -1;
    }

    public function sortBySize($a, $b)
    {
        return (($a['is_dir'] === $b['is_dir']) ? ($a['size'] > $b['size']) : ($a['is_dir'] < $b['is_dir'])) ? 1 : -1;
    }

    public function sortByTime($a, $b)
    {
        return ($a['time'] > $b['time']) ? 1 : -1;
    }

    public function humanizeFilesize($val)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $power = min(floor(($val ? log($val) : 0) / log(1024)), count($units) - 1);
        $val = sprintf('%.1f %s', round($val / pow(1024, $power), 1), $units[$power]);

        return str_replace('.0 ', ' ', $val);
    }

    public function generateTitle($forSubtitle = false)
    {
        $path = htmlentities($this->path);
        $title = htmlentities(str_replace(
            array('[items]', '[size]'),
            array($this->total, $this->humanizeFilesize($this->totalSize)),
            $this->config[$forSubtitle ? 'page_subtitle' : 'page_title']
        ));

        if ($this->config['show_breadcrumbs']) {
            $path = sprintf('<a href="%s">%s</a>', htmlentities($this->buildLink(array('b' => ''))), $path);
        }

        if (!empty($this->getBrowse())) {
            $path .= ($this->path !== '/') ? '/' : '';
            $items = explode('/', trim($this->browse, '/'));

            foreach ($items as $i => $item) {
                $path .= $this->config['show_breadcrumbs']
                    ? sprintf(
                        '<a href="%s">%s</a>',
                        htmlentities($this->buildLink(array('b' => implode('/', array_slice($items, 0, $i + 1))))),
                        htmlentities($item)
                    )
                    : htmlentities($item);
                $path .= (count($items) > ($i + 1)) ? '/' : '';
            }
        }

        return str_replace('[path]', $path, $title);
    }

    public function buildLink($changes)
    {
        $params = $_GET;

        foreach ($changes as $k => $v) {
            if (is_null($v)) {
                unset($params[$k]);
            } else {
                $params[$k] = $v;
            }
        }

        foreach ($params as $k => $v) {
            $params[$k] = urlencode($k) . '=' . urlencode($v);
        }

        return empty($params) ? $this->self : $this->self . '?' . implode('&', $params);
    }
}

$pdl = new PHPDirLister($config);
$items = $pdl->getListings('.' . (empty($pdl->getBrowse()) ? '' : '/' . $pdl->getBrowse()));
$sorting = isset($_GET['s']) ? $_GET['s'] : null;

switch ($sorting) {
    case 'size':
        $sorting = 'size';
        usort($items, function ($a, $b) use ($pdl) {
            return $pdl->sortBySize($a, $b);
        });
        break;

    case 'time':
        $sorting = 'time';
        usort($items, function ($a, $b) use ($pdl) {
            return $pdl->sortByTime($a, $b);
        });
        break;

    default:
        $sorting = 'name';
        usort($items, function ($a, $b) use ($pdl) {
            return $pdl->sortByName($a, $b);
        });
        break;
}

date_default_timezone_set($pdl->getConfig('timezone', 'UTC'));

$reverse = isset($_GET['r']) && $_GET['r'] === '1';
$items = $reverse ? array_reverse($items) : $items;


if ($pdl->getConfig('show_parent') && $pdl->getPath() !== '/' && empty($pdl->getBrowse())) {
    array_unshift($items, array('name' => '..', 'is_parent' => true, 'is_dir' => true, 'size' => 0, 'time' => 0));
}

?>
<!DOCTYPE HTML>
<html lang="en-US">

<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo strip_tags($pdl->generateTitle()) ?></title>
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
            border: none;
        }

        body {
            text-align: center;
            font-family: sans-serif;
            font-size: 13px;
            color: #000000;
        }

        #wrapper {
            width: 80%;
            margin: 0 auto;
            text-align: left;
        }

        body#left {
            text-align: left;
        }

        body#left #wrapper {
            margin: 0 20px;
        }

        h1 {
            font-size: 21px;
            padding: 0 10px;
            margin: 20px 0 0;
            font-weight: bold;
        }

        h2 {
            font-size: 11px;
            padding: 0 10px;
            margin: 10px 0 0;
            color: #98a6ad;
            font-weight: normal;
        }

        a {
            color: #003399;
            text-decoration: none;
        }

        a:hover {
            color: #0066cc;
            text-decoration: underline;
        }

        ul#header {
            margin-top: 20px;
        }

        ul li {
            display: block;
            list-style-type: none;
            overflow: hidden;
            padding: 10px;
        }

        ul li:hover {
            background-color: #f3f3f3;
        }

        ul li .date {
            text-align: center;
            width: 120px;
        }

        ul li .size {
            text-align: right;
            width: 90px;
        }

        ul li .date,
        ul li .size {
            float: right;
            font-size: 12px;
            display: block;
            color: #666666;
        }

        ul#header li {
            font-size: 11px;
            font-weight: bold;
            border-bottom: 1px solid #dee2e6;
        }

        ul#header li:hover {
            background-color: transparent;
        }

        ul#header li * {
            color: #888888;
            font-size: 11px;
        }

        ul#header li a:hover {
            color: #333333;
        }

        ul#header li .asc span,
        ul#header li .desc span {
            padding-right: 15px;
            background-position: right center;
            background-repeat: no-repeat;
        }

        ul#header li .asc span {
            background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAABbSURBVCjPY/jPgB8yDCkFB/7v+r/5/+r/i/7P+N/3DYuC7V93/d//fydQ0Zz/9eexKFgtsejLiv8b/8/8X/WtUBGrGyZLdH6f8r/sW64cTkdWSRS+zpQbgiEJAI4UCqdRg1A6AAAAAElFTkSuQmCC');
        }

        ul#header li .desc span {
            background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAABbSURBVCjPY/jPgB8yDDkFmyVWv14kh1PBeoll31f/n/ytUw6rgtUSi76s+L/x/8z/Vd8KFbEomPt16f/1/1f+X/S/7X/qeSwK+v63/K/6X/g/83/S/5hvQywkAdMGCdCoabZeAAAAAElFTkSuQmCC');
        }

        ul li.item {
            border-top: 1px solid #f3f3f3;
        }

        ul li.item:first-child {
            border-top: none;
        }

        ul li.item .name {
            font-weight: bold;
        }

        ul li.item .dir,
        ul li.item .file {
            padding-left: 20px;
            background-position: left center;
            background-repeat: no-repeat;
        }

        ul li.item .dir {
            background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAGrSURBVDjLxZO7ihRBFIa/6u0ZW7GHBUV0UQQTZzd3QdhMQxOfwMRXEANBMNQX0MzAzFAwEzHwARbNFDdwEd31Mj3X7a6uOr9BtzNjYjKBJ6nicP7v3KqcJFaxhBVtZUAK8OHlld2st7Xl3DJPVONP+zEUV4HqL5UDYHr5xvuQAjgl/Qs7TzvOOVAjxjlC+ePSwe6DfbVegLVuT4r14eTr6zvA8xSAoBLzx6pvj4l+DZIezuVkG9fY2H7YRQIMZIBwycmzH1/s3F8AapfIPNF3kQk7+kw9PWBy+IZOdg5Ug3mkAATy/t0usovzGeCUWTjCz0B+Sj0ekfdvkZ3abBv+U4GaCtJ1iEm6ANQJ6fEzrG/engcKw/wXQvEKxSEKQxRGKE7Izt+DSiwBJMUSm71rguMYhQKrBygOIRStf4TiFFRBvbRGKiQLWP29yRSHKBTtfdBmHs0BUpgvtgF4yRFR+NUKi0XZcYjCeCG2smkzLAHkbRBmP0/Uk26O5YnUActBp1GsAI+S5nRJJJal5K1aAMrq0d6Tm9uI6zjyf75dAe6tx/SsWeD//o2/Ab6IH3/h25pOAAAAAElFTkSuQmCC');
        }

        ul li.item .file {
            background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAABVklEQVQYGQXBP0hUAQAH4O/dnaaSXhIY2Z/NDAoaanUNorHJpbHBotZwT2hpL4iioTjUqb0hh3AqwYgw4xITgtCOvLI73/v1fUUArCx0bu5NMr7TfDEzDyAiYnmi1UuSJGn1Xk5GRBSxstCZ3Tur1jRlWnzyVYdqfKv5amZetA6SJGlnKWtZy2LaSZKkdRD1S8/qV+K4+OxQ2w9jSidVPuo03k0XS/2Lja5C3YYbgGVT+rpKm4e1dE85rWnUhA2VyoYTBu3pGZduIxgy4LcBHevo23VgABGNICrDzvij8tNfoxoKpVI0yuGa6Cv1FSqHjoCeQqUcquXtqo4RdZWgEvHPmJpNWa1V17dvvfnyQWFMA6WeEUe1rX/bvZtrRfB8Ivcyd/7YOaVthR1b+3mSR3e+UwTw9ELmM3u5+GXb/nIe3H4PFAHA46u5n8E8nHsNQBEAAADAf9MfuSUN80DGAAAAAElFTkSuQmCC');
        }

        #footer {
            color: #98a6ad;
            font-size: 11px;
            margin-top: 40px;
            margin-bottom: 20px;
            padding: 0 10px;
            text-align: left;
        }

        #footer a {
            color: #98a6ad;
            font-weight: bold;
        }

        #footer a:hover {
            color: #777777;
        }
    </style>

</head>

<body <?php if ($pdl->getConfig('content_alignment') === 'left') echo 'id="left"' ?>>

    <div id="wrapper">

        <h1><?php echo $pdl->generateTitle() ?></h1>
        <h2><?php echo $pdl->generateTitle(true) ?></h2>

        <ul id="header">
            <li>
                <a href="<?php echo $pdl->buildLink(array('s' => 'size', 'r' => (!$reverse && $sorting === 'size') ? '1' : null)) ?>" class="size <?php if ($sorting == 'size') echo $reverse ? 'desc' : 'asc' ?>"><span>Size</span></a>
                <a href="<?php echo $pdl->buildLink(array('s' => 'time', 'r' => (!$reverse && $sorting === 'time') ? '1' : null)) ?>" class="date <?php if ($sorting == 'time') echo $reverse ? 'desc' : 'asc' ?>"><span>Last Modified</span></a>
                <a href="<?php echo $pdl->buildLink(array('s' =>  null, 'r' => (!$reverse && $sorting === 'name') ? '1' : null)) ?>" class="name <?php if ($sorting == 'name') echo $reverse ? 'desc' : 'asc' ?>"><span>Name</span></a>
            </li>
        </ul>

        <ul>
            <?php foreach ($items as $item) : ?>
                <li class="item">
                    <span class="size">
                        <?php echo $item['is_dir'] ? '-' : $pdl->humanizeFilesize($item['size']) ?>
                    </span>
                    <span class="date">
                        <?php echo ((isset($item['is_parent']) && $item['is_parent']) || empty($item['time'])) ? '-' : date($pdl->getConfig('date_format'), $item['time']) ?>
                    </span>

                    <?php
                    if ($item['is_dir'] && $pdl->getConfig('browse_directories') && (!isset($item['is_parent']) || !$item['is_parent'])) {
                        if ($item['name'] === '..') {
                            $link = $pdl->buildLink(array('b' => substr($pdl->getBrowse(), 0, strrpos($pdl->getBrowse(), '/'))));
                        } else {
                            $link = $pdl->buildLink(array('b' => (empty($pdl->getBrowse()) ? '' : (string) $pdl->getBrowse() . '/') . $item['name']));
                        }
                    } else {
                        $link = (empty($pdl->getBrowse()) ? '' : str_replace(['%2F', '%2f'], '/', rawurlencode((string)$pdl->getBrowse())) . '/') . rawurlencode($item['name']);
                    }
                    ?>
                    <a href="<?php echo htmlentities($link) ?>" class="name <?php echo $item['is_dir'] ? 'dir' : 'file' ?>"><?php echo htmlentities($item['name']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($pdl->getConfig('show_footer')) : ?>
            <p id="footer">
                Powered by <a href="https://github.com/esyede/php-dirlister" target="_blank">PHPDirLister</a>, simple directory indexer
                <br>
                Icons by <a href="https://github.com/markjames/famfamfam-silk-icons" target="_blank">FamFamFam (Mark James)</a>
            </p>
        <?php endif; ?>
    </div>
</body>

</html>
