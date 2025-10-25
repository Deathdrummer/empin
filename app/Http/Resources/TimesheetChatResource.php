<?php namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TimesheetChatResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request) {
        $currentUser = auth('site')->user();

        return [
            'id'        => $this->id,
            'day'       => $this->day,
            'message'   => $this->message,
            'created_at'=> $this->created_at->translatedFormat('d F Y г. в H:i'),
            'updated_at'=> $this->updated_at,
			'self'		=> $currentUser ? ($this->from_id === $currentUser->staff_id) : false,
			'from' 	=> $this->profile ? [
							'id'		=> $this->from_id,
							'full_name' => $this->profile->full_name,
							'sname' 	=> $this->profile->sname,
							'fname' 	=> $this->profile->fname,
							'mname' 	=> $this->profile->mname,
						] : null,

        ];
    }
	
	
	
	
}
