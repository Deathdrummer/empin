<?php namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TimesheetContractResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request) {
        return [
			'timesheet_contract_id'	=> $this->id,
			'contract_id' 			=> $this->contract_id,
			'done' 					=> $this->done,
			'created_at' 			=> $this->created_at,
			'updated_at' 			=> $this->updated_at,
			'object_number' 		=> optional($this->contract)->object_number,
			'title' 				=> optional($this->contract)->title,
			'titul' 				=> optional($this->contract)->titul,
			'chat'					=> TimesheetChatResource::collection($this->chat)->resolve(),
		];
    }
}
