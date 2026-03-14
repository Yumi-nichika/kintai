<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'request_start_time' => ['required', 'date_format:H:i'],
            'request_end_time' => ['required', 'date_format:H:i'],

            'request_break_start_time.*' => ['nullable', 'date_format:H:i'],
            'request_break_end_time.*' => ['nullable', 'date_format:H:i'],

            'request_note' => ['required'],
        ];
    }

    public function messages()
    {
        return [
            'request_start_time.required' => '出勤時間を記入してください',
            'request_start_time.date_format' => '出勤時間は半角で「00:00」形式で記入してください',
            'request_end_time.required' => '退勤時間を記入してください',
            'request_end_time.date_format' => '退勤時間は半角で「00:00」形式で記入してください',
            'request_break_start_time.*.date_format' => '休憩開始時間は半角で「00:00」形式で記入してください',
            'request_break_end_time.*.date_format' => '休憩終了時間は半角で「00:00」形式で記入してください',
            'request_note.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $start = Carbon::parse($this->request_start_time);
            $end   = Carbon::parse($this->request_end_time);

            $breakStarts = $this->request_break_start_time ?? [];
            $breakEnds   = $this->request_break_end_time ?? [];

            $breakPeriods = [];

            $this->validateWorkTime($validator, $start, $end);
            $this->validateBreakExist($validator, $breakStarts);

            foreach ($breakStarts as $i => $breakStart) {

                $breakEnd = $breakEnds[$i] ?? null;

                /*
                休憩入力整合性チェック
                */
                if ($breakStart && !$breakEnd) {
                    $validator->errors()->add(
                        "request_break_end_time.$i",
                        '休憩終了時間を入力してください'
                    );
                    continue;
                }

                if (!$breakStart && $breakEnd) {
                    $validator->errors()->add(
                        "request_break_start_time.$i",
                        '休憩開始時間を入力してください'
                    );
                    continue;
                }

                if (!$breakStart || !$breakEnd) {
                    continue;
                }

                $breakStartTime = Carbon::parse($breakStart);
                $breakEndTime   = Carbon::parse($breakEnd);

                $this->validateBreakOrder($validator, $i, $breakStartTime, $breakEndTime);
                $this->validateBreakRange($validator, $i, $breakStartTime, $breakEndTime, $start, $end);

                $breakPeriods[] = [
                    'start' => $breakStartTime,
                    'end'   => $breakEndTime
                ];
            }
            $this->validateBreakOverlap($validator, $breakPeriods);
        });
    }

    /*
    出勤 > 退勤
    */
    private function validateWorkTime($validator, $start, $end)
    {
        if ($start->gt($end)) {
            $validator->errors()->add(
                'request_start_time',
                '出勤時間もしくは退勤時間が不適切な値です'
            );
        }
    }

    /*
    休憩が1つもない
    */
    private function validateBreakExist($validator, $breakStarts)
    {
        if (count(array_filter($breakStarts)) === 0) {
            $validator->errors()->add(
                'request_break_start_time.0',
                '休憩時間を記入してください'
            );
        }
    }

    /*
    休憩開始 > 休憩終了
    */
    private function validateBreakOrder($validator, $index, $start, $end)
    {
        if ($start->gt($end)) {
            $validator->errors()->add(
                "request_break_end_time.$index",
                '休憩時間が不適切な値です'
            );
        }
    }

    /*
    勤務時間外チェック
    */
    private function validateBreakRange($validator, $index, $breakStart, $breakEnd, $workStart, $workEnd)
    {
        // 休憩開始 < 出勤時間 || 休憩開始 > 退勤時間
        if ($breakStart->lt($workStart) || $breakStart->gt($workEnd)) {
            $validator->errors()->add(
                "request_break_start_time.$index",
                '休憩時間が不適切な値です'
            );
        }

        // 休憩終了 > 退勤時間
        if ($breakEnd->gt($workEnd)) {
            $validator->errors()->add(
                "request_break_end_time.$index",
                '休憩時間もしくは退勤時間が不適切な値です'
            );
        }
    }

    /*
    休憩重複チェック
    */
    private function validateBreakOverlap($validator, $breakPeriods)
    {
        for ($i = 0; $i < count($breakPeriods); $i++) {
            for ($j = $i + 1; $j < count($breakPeriods); $j++) {
                $start1 = $breakPeriods[$i]['start'];
                $end1   = $breakPeriods[$i]['end'];

                $start2 = $breakPeriods[$j]['start'];
                $end2   = $breakPeriods[$j]['end'];

                if ($start1->lt($end2) && $start2->lt($end1)) {
                    $validator->errors()->add(
                        'request_break_start_time.' . $j,
                        '休憩時間が重複しています'
                    );
                }
            }
        }
    }
}
