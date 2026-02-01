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
        //add image url to icon

        $socialLinks = $data ? json_decode($data, true) : [];
        foreach ($socialLinks as &$link) {
            if (isset($link['icon']) && is_string($link['icon']) && !empty($link['icon'])) {
                $link['icon'] = url('storage/' . $link['icon']);
            }
        }
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


        $existingSettings = Setting::where('key', 'contact_info')->value('value');
        $existingLinks = $existingSettings ? json_decode($existingSettings, true) : [];

        $linksMap = [];
        foreach ($existingLinks as $link) {
            if (isset($link['platform'])) {
                $linksMap[strtolower($link['platform'])] = $link;
            }
        }
        foreach ($request->social_links as $index => $newLink) {

            $platformKey = strtolower($newLink['platform']);


            if ($request->hasFile("social_links.$index.icon")) {

                if (
                    isset($linksMap[$platformKey]['icon']) &&
                    is_string($linksMap[$platformKey]['icon']) &&
                    !empty($linksMap[$platformKey]['icon'])
                ) {
                    $this->deleteFile($linksMap[$platformKey]['icon']);
                }

                $newLink['icon'] = $this->handleFileUpload(
                    $request,
                    "social_links.$index.icon",
                    'social_icons',
                    null,
                    null,
                    90,
                    true
                );
            } else {

                if (isset($linksMap[$platformKey])) {
                    $newLink['icon'] = $linksMap[$platformKey]['icon'];
                } else {
                    $newLink['icon'] = is_string($newLink['icon']) ? $newLink['icon'] : '';
                }
            }

            $linksMap[$platformKey] = $newLink;
        }

        $finalLinks = array_values($linksMap);

        Setting::updateOrCreate(
            ['key' => 'contact_info'],
            ['value' => json_encode($finalLinks)]
        );

        return response_success('Contact info updated/added successfully.');
    }

    public function deleteContactInfo(Request $request)
    {
        $request->validate([
            'platform' => 'required|string',
        ]);

        $platformToDelete = strtolower($request->platform);

        $existingSettings = Setting::where('key', 'contact_info')->value('value');
        $existingLinks = $existingSettings ? json_decode($existingSettings, true) : [];

        $finalLinks = [];
        $isDeleted = false;

        foreach ($existingLinks as $link) {

            if (
                isset($link['platform']) &&
                strtolower($link['platform']) === $platformToDelete
            ) {
                if (
                    isset($link['icon']) &&
                    is_string($link['icon']) &&
                    !empty($link['icon'])
                ) {
                    $this->deleteFile($link['icon']);
                }

                $isDeleted = true;
                continue;
            }

            $finalLinks[] = $link;
        }

        if (!$isDeleted) {
            return response_error('Platform not found.', [], 404);
        }

        Setting::updateOrCreate(
            ['key' => 'contact_info'],
            ['value' => json_encode($finalLinks)]
        );

        return response_success('Contact info deleted successfully.');
    }

    //update shipping cost
    public function updateTaxAndShipping(Request $request)
    {
        $request->validate([
            'shipping_cost' => 'sometimes|numeric|min:0',
            'tax_percentage' => 'sometimes|numeric|min:0',
        ]);

        if ($request->has('shipping_cost')) {
            Setting::setValue('shipping_cost', $request->shipping_cost);
        }
        if ($request->has('tax_percentage')) {
            Setting::setValue('tax_percentage', $request->tax_percentage);
        }
        return response_success('Shipping cost updated successfully.', [
            'shipping_cost' => (float) Setting::getValue('shipping_cost', 0),
            'tax_percentage' => (float) Setting::getValue('tax_percentage', 0),
        ]);
    }
    public function getTaxAndShipping()
    {
        $shippingCost = Setting::getValue('shipping_cost', 0);
        $taxPercentage = Setting::getValue('tax_percentage', 0);

        return response_success('Tax and shipping info fetched successfully.', [
            'shipping_cost' => (float) $shippingCost,
            'tax_percentage' => (float) $taxPercentage,
        ]);
    }
}
