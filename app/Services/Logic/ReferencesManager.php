<?php

namespace STS\Services\Logic;

use STS\Entities\References as ReferencesModel;
use Validator;
use STS\Contracts\Repository\IReferencesRepository as ReferencesRepo;
use STS\User as UserModel;
use STS\Contracts\Logic\IReferencesLogic;

class ReferencesManager extends BaseManager implements IReferencesLogic
{
    protected $referencesRepo;

    public function __construct(ReferencesRepo $referencesRepo)
    {
        $this->referencesRepo = $referencesRepo;
    }

    public function validator(array $data)
    {
        return Validator::make($data, [
            'comment' => 'required|string|max:260',
            'user_id_to' => 'required'
        ]);
    }

    public function create(UserModel $user, $data) 
    {
        $v = $this->validator($data);
        if ($v->fails()) {
            $this->setErrors($v->errors());

            return;
        }
        $userTo = UserModel::find($data['user_id_to']);
        if (!$userTo) {
            $this->setErrors(['error' => 'user_doesnt_exist']);
            return;
        }
        if ($userTo->id == $user->id) {
            $this->setErrors(['error' => 'reference_same_user']);
            return;
        }

        $referenceExist = ReferencesModel::where('user_id_to', $userTo->id)
            ->where('user_id_from', $user->id)->get();

        if ($referenceExist && count($referenceExist)) {
            $this->setErrors(['error' => 'reference_exist']);
            return;
        }

        $reference = new ReferencesModel();
        $reference->user_id_from = $user->id;
        $reference->user_id_to = $data['user_id_to'];
        $reference->comment = $data['comment'];
        $this->referencesRepo->create($reference);
        return $reference;
    }
}