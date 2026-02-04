<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyReflectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $allQuestions = [
            [
                'key' => 'grateful_for',
                'title' => "What are you grateful for today?",
                'answer' => $this->grateful_for
            ],
            [
                'key' => 'improvement_focus',
                'title' => "One thing you'll focus on improving?",
                'answer' => $this->improvement_focus
            ],
            [
                'key' => 'inspired_by',
                'title' => "Who inspired you today and why?",
                'answer' => $this->inspired_by
            ],
            [
                'key' => 'weekly_progress',
                'title' => "How do you feel about your progress?",
                'answer' => $this->weekly_progress
            ],
        ];


        $filledQuestions = array_filter($allQuestions, function ($q) {
            return !empty($q['answer']);
        });


        $finalQuestions = collect($filledQuestions)->values()->map(function ($item, $index) {
            return [
                'sl' => $index + 1,
                'key' => $item['key'],
                'title' => $item['title'],
                'answer' => $item['answer']
            ];
        });

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'entry_date' => $this->entry_date->format('Y-m-d'),
            'display_date' => $this->entry_date->format('M d, Y'),

            'questions' => $finalQuestions,

            'created_at' => $this->created_at,
        ];
    }
}
