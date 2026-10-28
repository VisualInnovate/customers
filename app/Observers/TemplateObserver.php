<?php
namespace App\Observers;
use App\Models\Template;
use App\Services\TemplateCacheService;
class TemplateObserver
{
    public function created(Template $template): void
    {
        TemplateCacheService::invalidate($template);
    }
    public function updated(Template $template): void
    {
        TemplateCacheService::invalidate($template);
    }
    public function deleted(Template $template): void
    {
        TemplateCacheService::invalidate($template);
    }
}
