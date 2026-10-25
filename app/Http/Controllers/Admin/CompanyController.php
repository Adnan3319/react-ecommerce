<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function edit()
    {
        $company = Company::firstOrCreate(
            ['id' => 1],
            ['name' => 'Company-Website']
        );

        return Inertia::render('Admin/Company/Edit', [
            'company' => $company,
        ]);
    }

    public function update(UpdateCompanyRequest $request)
    {
        $company = Company::firstOrCreate(['id' => 1]);
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $request->file('logo')->store('company', 'public');
        } else {
            unset($data['logo']);
        }

        if ($request->hasFile('about_bg_image')) {
            if ($company->about_bg_image) {
                Storage::disk('public')->delete($company->about_bg_image);
            }
            $data['about_bg_image'] = $request->file('about_bg_image')->store('company', 'public');
        } else {
            unset($data['about_bg_image']);
        }

        $company->update($data);

        return redirect()->back();
    }
}