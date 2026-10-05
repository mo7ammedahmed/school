<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\ContentPage;
use App\Domain\Content\Services\PublicWebsiteContent;
use Inertia\Response;

class PageController extends PublicController
{
    public function show(string $slug, PublicWebsiteContent $website): Response
    {
        $page = ContentPage::forSchool($this->schoolId())->published()->where('slug', $slug)->firstOrFail();

        return $website->render($page);
    }
}
