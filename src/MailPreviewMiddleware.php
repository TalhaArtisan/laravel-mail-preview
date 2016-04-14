<?php

namespace Themsaid\MailPreview;

use Closure;
use Illuminate\Http\Response;

class MailPreviewMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $response = $next($request);
        } catch (\Exception $e) {
            $response = $this->handleException($request, $e);
        } catch (\Throwable $e) {
            $response = $this->handleException($request, $e);
        }

        if ($response instanceOf Response && $previewPath = $request->session()->get('mail_preview')) {
            $this->addLinkToResponse($response, $previewPath);

            $request->session()->forget('mail_preview');
        }

        return $response;
    }

    /**
     * Modify the response to add link to the email preview.
     *
     * @param $response
     * @param $previewPath
     */
    private function addLinkToResponse($response, $previewPath)
    {
        if (app()->runningInConsole()) {
            return;
        }

        $content = $response->getContent();

        $linkHTML = "<div id='MailPreviewDriverBox' style='
            position:absolute;
            top:0;
            z-index:99999;
            background:#fff;
            border:solid 1px #ccc;
            padding: 15px;
            '>
        An email was just sent: <a href='".url('/mail-preview-driver?path='.$previewPath)."'>Preview Sent Email</a>
        </div>";

        $linkHTML .= "<script type=\"text/javascript\">";

        $linkHTML .= "setTimeout(function(){ 
        document.body.removeChild(document.getElementById('MailPreviewDriverBox')); 
        }, 8000);";

        $linkHTML .= "</script>";

        $bodyPosition = strripos($content, '</body>');

        if (false !== $bodyPosition) {
            $content = substr($content, 0, $bodyPosition).$linkHTML.substr($content, $bodyPosition);
        }

        $response->setContent($content);
    }
}