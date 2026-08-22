<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One leader's answer to one of the camp's own review questions.
 *
 * @property int $id
 * @property int $day_review_id
 * @property int $feedback_question_id
 * @property string $answer
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class FeedbackAnswer extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'day_review_id',
        'feedback_question_id',
        'answer',
    ];

    /**
     * @return BelongsTo<DayReview, $this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(DayReview::class, 'day_review_id');
    }

    /**
     * @return BelongsTo<FeedbackQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(FeedbackQuestion::class, 'feedback_question_id');
    }
}
