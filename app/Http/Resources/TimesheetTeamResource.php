<?php namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TimesheetTeamResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request) {
        return [
			'id'	=> $this->id,
			'master' => $this->profile ? [
				'id' => $this->staff_id,
				'sname' => $this->profile->sname,
				'fname' => $this->profile->fname,
				'mname' => $this->profile->mname,
			] : null,
			'contracts' => TimesheetContractResource::collection($this->contracts)->resolve(),
		];
    }
}
