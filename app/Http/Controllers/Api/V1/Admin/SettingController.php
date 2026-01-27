<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use FileUploadTrait;
    public function getTerms()
    {
        $content = Setting::where('key', 'terms_and_conditions')->value('value') ?? '';

        return response_success('Terms fetched successfully.', $content);
    }

    public function updateTerms(Request $request)
    {
        $request->validate(['content' => 'required|string']);

        Setting::updateOrCreate(
            ['key' => 'terms_and_conditions'],
            ['value' => $request->content]
        );

        return response_success('Terms updated successfully.');
    }

    // ======================================================
    // CONTACT INFO (SOCIAL LINKS)
    // ======================================================

    public function getContactInfo()
    {
        $data = Setting::where('key', 'contact_info')->value('value');
        $socialLinks = $data ? json_decode($data, true) : [];

        return response_success('Contact info fetched successfully.', $socialLinks);
    }

    public function updateContactInfo(Request $request)
    {
        $request->validate([
            'social_links' => 'required|array',
            'social_links.*.platform' => 'required|string',
            'social_links.*.icon' => 'required',
            'social_links.*.url' => 'required|url',
        ]);

        $oldLinks = Setting::where('key', 'contact_info')->value('value');
        $oldLinks = $oldLinks ? json_decode($oldLinks, true) : [];

        $processedLinks = [];

        foreach ($request->social_links as $index => $link) {

            if ($request->hasFile("social_links.$index.icon")) {

                if (
                    isset($oldLinks[$index]['icon']) &&
                    is_string($oldLinks[$index]['icon']) &&
                    !empty($oldLinks[$index]['icon'])
                ) {
                    $this->deleteFile($oldLinks[$index]['icon']);
                }

                $link['icon'] = $this->handleFileUpload(
                    $request,
                    "social_links.$index.icon",
                    'social_icons',
                    64,
                    64,
                    90,
                    true
                );
            } else {
                $link['icon'] = is_string($link['icon']) ? $link['icon'] : '';
            }

            $processedLinks[] = $link;
        }

        Setting::updateOrCreate(
            ['key' => 'contact_info'],
            ['value' => json_encode($processedLinks)]
        );

        return response_success('Contact info updated successfully.');
    }
}
