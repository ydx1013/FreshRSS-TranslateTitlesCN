<?php
require_once('TranslationService.php');

class TranslateController {
    // 函数名从 translateTitle 改为 translateText
    public function translateText($text) {
        if (empty($text)) {
            error_log("TranslateTitlesCN: Empty text provided");
            return '';
        }

        $serviceType = FreshRSS_Context::$user_conf->TranslateService ?? 'google';
        $translationService = new TranslationService($serviceType);
        $translatedText = '';
        $attempts = 0;
        $sleepTime = 1; // 初始等待时间

        error_log("TranslateTitlesCN: Service: " . $serviceType . ", Text: " . substr($text, 0, 50) . "...");

        while ($attempts < 2) {
            try {
                $translatedText = $translationService->translate($text);
                if (!empty($translatedText)) {
                    error_log("TranslateTitlesCN: Translation successful: " . substr($translatedText, 0, 50) . "...");
                    break;
                }
                error_log("TranslateTitlesCN: Empty translation result on attempt " . ($attempts + 1));
            } catch (Exception $e) {
                error_log("TranslateTitlesCN: Translation error on attempt " . ($attempts + 1) . " - " . $e->getMessage());
                $attempts++;
                sleep($sleepTime);
                $sleepTime *= 2; // 每次失败后增加等待时间
            }
        }

        // 如果翻译失败且当前服务为DeeplX，则尝试使用谷歌翻译
        if (empty($translatedText) && $serviceType == 'deeplx') {
            error_log("TranslateTitlesCN: DeeplX failed, falling back to Google Translate");
            $translationService = new TranslationService('google');
            try {
                $translatedText = $translationService->translate($text);
                if (!empty($translatedText)) {
                    error_log("TranslateTitlesCN: Google Translate fallback successful: " . substr($translatedText, 0, 50) . "...");
                }
            } catch (Exception $e) {
                error_log("TranslateTitlesCN: Google Translate fallback failed - " . $e->getMessage());
            }
        }

        // 如果翻译仍然失败，返回原始文本
        if (empty($translatedText)) {
            error_log("TranslateTitlesCN: All translation attempts failed, returning original text");
            return $text;
        }

        return $translatedText;
    }
}
