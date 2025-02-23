<?php

namespace App\Repositories;

use App\Models\AdSpaces;

/**
 * Class StaffRepository
 *
 * @version August 6, 2021, 10:17 am UTC
 */
class AdSpacesRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'password',
        'gender',
        'role',
    ];

    /**
     * Return searchable fields
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return AdSpaces::class;
    }

    public function store($input): bool
    {
        $data = AdSpaces::whereAdSpaces($input['ad_space'])->get();

        foreach ($data as $key => $value) {
            $value->update([
                'ad_url' => $input['ad_url'][$key],
                'code' => $input['ad_code'][$key],
            ]);

            if (! empty($input['ad_banner'][$key])) {
                $value->clearMediaCollection(AdSpaces::IMAGE_POST);
                $value->addMedia($input['ad_banner'][$key])->toMediaCollection(AdSpaces::IMAGE_POST, config('app.media_disc'));
            }
        }

        return true;
    }
}
