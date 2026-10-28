<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Models\Company;
use App\Services\Company\CompanyBranding;
use App\Services\Media\FileUploadService;
use App\Services\Order\DeliveryZones;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    private const LOGO_DIRECTORY = 'company';

    private const LOGO_PRESET = 'logo';

    public function __construct(private readonly FileUploadService $uploads) {}

    public function edit(): Response
    {
        $company = Company::first();

        return Inertia::render('Admin/Company/Edit', [
            'company' => [
                ...$company->toArray(),
                'delivery_charge_inside' => Money::toTaka($company->delivery_charge_inside),
                'delivery_charge_outside' => Money::toTaka($company->delivery_charge_outside),
            ],
        ]);
    }

    public function update(UpdateCompanyRequest $request): RedirectResponse
    {
        $company = Company::first();
        $data = $request->safe()->except(['logo', 'remove_logo']);
        $oldLogo = $company->logo;

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->uploads->upload($request->file('logo'), self::LOGO_DIRECTORY, self::LOGO_PRESET);
        } elseif ($request->boolean('remove_logo')) {
            $data['logo'] = null;
        }

        $data['delivery_charge_inside'] = Money::toMinor($data['delivery_charge_inside']);
        $data['delivery_charge_outside'] = Money::toMinor($data['delivery_charge_outside']);

        $company->update($data);

        if ($oldLogo !== null && $company->logo !== $oldLogo) {
            $this->uploads->delete($oldLogo);
        }

        DeliveryZones::flush();
        CompanyBranding::flush();

        return redirect()->back();
    }
}
