<?php
/**
 * @author      Serge Postrash aka SDKiller <jexy.ru@gmail.com>
 * @link        https://github.com/SDKiller
 * @copyright   Copyright (c) 2015 Serge Postrash
 * @license     BSD 3-Clause, see LICENSE.md
 */

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\helpers\FileHelper;


class RedactorUpdateController extends Controller
{
    public $pluginsUrl = 'http://imperavi.com/webdownload/redactor/plugin/?plugin=';

    public $plugins = [
        'clips',
        'counter',
        'definedlinks',
        'filemanager',
        'fontcolor',
        'fontfamily',
        'fontsize',
        'fullscreen',
        'imagemanager',
        'limiter',
        'table',
        'textdirection',
        'textexpander',
        'video',
    ];

    protected $tmpDir = '@runtime/download';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->tmpDir = Yii::getAlias($this->tmpDir);
        FileHelper::createDirectory($this->tmpDir . '/zip');
    }

    /**
     * @return int
     */
    public function actionDownloadPlugins()
    {
        foreach ($this->plugins as $pluginName) {
            $msg = ' ';
            try {
                $buff = file_get_contents($this->pluginsUrl . $pluginName);
            } catch (\Exception $e) {
                $buff = false;
                $msg = $e->getMessage();
            }
            if ($buff === false) {
                $this->stdout('Error downloading plugin ' . $pluginName . $msg . PHP_EOL);
            } else {
                try {
                    $res = file_put_contents($this->tmpDir . '/zip' . '/' . $pluginName . '.zip', $buff, FILE_BINARY);
                } catch (\Exception $e) {
                    $res = false;
                    $msg = $e->getMessage();
                }
                if ($res === false) {
                    $this->stdout('Error copying file ' . $pluginName . '.zip' . $msg . PHP_EOL);
                } else {
                    $this->stdout('OK dowloaded plugin ' . $pluginName . PHP_EOL);
                }
            }
        }

        return 0;
    }

    /**
     * @return int
     * @throws \yii\base\Exception
     */
    public function actionUnpackPlugins()
    {
        $files = FileHelper::findFiles($this->tmpDir . '/zip', ['only' => ['*.zip']]);

        if (empty($files)) {
            $this->stdout('No files found' . PHP_EOL);
            return 1;
        }

        foreach ($files as $file) {
            $pluginName = pathinfo($file, PATHINFO_FILENAME);

            if (!in_array($pluginName, $this->plugins, true)) {
                continue;
            }

            if ($pluginName == 'clips') {
                // clips is packed with folder
                $extractDir = $this->tmpDir . '/plugins';
            } else {
                $extractDir = $this->tmpDir . '/plugins/' . $pluginName;
            }
            FileHelper::createDirectory($extractDir);

            $zip = new \ZipArchive();
            if ($zip->open($file) !== true) {
                $this->stdout('Error opening file ' . $pluginName . '.zip' . PHP_EOL);
                continue;
            }
            if ($zip->extractTo($extractDir) !== true) {
                $this->stdout('Error extracting file ' . $pluginName . '.zip' . PHP_EOL);
                continue;
            }
            $zip->close();

            $garbage = $extractDir . '/__MACOSX';
            if (is_dir($garbage)) {
                FileHelper::removeDirectory($garbage);
            }
            $this->stdout('OK extracted plugin ' . $pluginName . PHP_EOL);
        }

        return 0;
    }

    /**
     * @return int
     */
    public function actionCleanup()
    {
        FileHelper::removeDirectory($this->tmpDir);

        return 0;
    }

}
