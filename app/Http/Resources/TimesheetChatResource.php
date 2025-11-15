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
        $currentUser = $request->user();

        // Получаем ID пользователя-автора через связь
        $authorUserId = $this->profile?->registred?->id;

        // Сравниваем ID пользователей
        $isSelf = $currentUser && $authorUserId ? ($authorUserId === $currentUser->id) : false;

        return [
            'id'        => $this->id,
            'day'       => $this->day,
            'message'   => $this->message,
            'created_at'=> $this->created_at->toIso8601String(),
            'updated_at'=> $this->updated_at,
			'self'		=> $isSelf,
            'API_VERSION' => 'v2.0', // ВРЕМЕННАЯ МЕТКА
            // DEBUG info
            'debug_author_user_id' => $authorUserId,
            'debug_current_user_id' => $currentUser?->id,
            'debug_has_user' => $currentUser !== null,
			'from' 	=> $this->profile ? [
							'id'		=> $authorUserId, // user_id вместо staff_id
							'staff_id'  => $this->from_id, // staff_id для справки
							'full_name' => $this->profile->full_name,
							'sname' 	=> $this->profile->sname,
							'fname' 	=> $this->profile->fname,
							'mname' 	=> $this->profile->mname,
						] : null,

        ];
    }
	
	
	
	
}
