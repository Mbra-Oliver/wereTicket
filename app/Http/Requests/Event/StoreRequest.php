<?php

namespace App\Http\Requests\Event;

use App\Models\Language;
use App\Rules\ImageMimeTypeRule;
use App\Models\Event\EventContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   *
   * @return bool
   */
  public function authorize()
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, mixed>
   */
  public function rules()
  {
 

    $request = $this->request->all();
    $ruleArray = [
      'slider_images' => 'required',
      'thumbnail' => [
        'required',
        new ImageMimeTypeRule(),
        // Dimension check as closure:
        function ($attribute, $value, $fail) {
          if ($value && is_file($value->getPathname())) {
            [$width, $height] = getimagesize($value->getPathname());
            if ($width != 320 || $height != 230) {
              $fail('The thumbnail image dimensions must be exactly 320x230 pixels.');
            }
          }
        }
      ],
      'status' => 'required',
      'is_featured' => 'required',
    ];

    if ($this->date_type == 'single') {
      $ruleArray['start_date'] = 'required';
      $ruleArray['start_time'] = 'required';
      $ruleArray['end_date'] = 'required';
      $ruleArray['end_time'] = 'required';
    }

    if ($this->date_type == 'multiple') {
      $ruleArray['m_start_date.*'] = 'required';
      $ruleArray['m_start_time.*'] = 'required';
      $ruleArray['m_end_date.*'] = 'required';
      $ruleArray['m_end_time.*'] = 'required';
    }

    if ($this->event_type == 'online') {
      $ruleArray['early_bird_discount_type'] = 'required';
      $ruleArray['meeting_url'] = 'required';
      $ruleArray['discount_type'] = 'required_if:early_bird_discount_type,enable';
      $ruleArray['early_bird_discount_amount'] = 'required_if:early_bird_discount_type,enable';
      $ruleArray['early_bird_discount_date'] = 'required_if:early_bird_discount_type,enable';
      $ruleArray['early_bird_discount_time'] = 'required_if:early_bird_discount_type,enable';
      $ruleArray['ticket_available_type'] = 'required';

      if ($this->filled('ticket_available_type') && $this->ticket_available_type == 'limited') {
        $ruleArray['ticket_available'] = 'required';
      }

      $ruleArray['max_ticket_buy_type'] = 'required';

      if ($this->filled('max_ticket_buy_type') && $this->max_ticket_buy_type == 'limited') {
        $ruleArray['max_buy_ticket'] = 'required';
      }

      if (!$this->filled('pricing_type')) {
        $ruleArray['price'] = 'required';
      }

      if ($request['early_bird_discount_type'] == 'enable' && $request['discount_type'] == 'percentage') {
        $ruleArray['early_bird_discount_amount'] = 'numeric|between:1,99';
      } elseif ($request['early_bird_discount_type'] == 'enable' && $request['discount_type'] == 'fixed') {
        $price = $request['price'] - 1;
        $ruleArray['early_bird_discount_amount'] = "numeric|between:1,$price";
      }
    }

    if ($this->event_type == 'venue') {
      $ruleArray['latitude'] = 'required_if:event_type,venue';
      $ruleArray['longitude'] = 'required_if:event_type,venue';
    }

    $bs = DB::table('basic_settings')
      ->select('event_country_status', 'event_state_status')
      ->first();
    $languages = Language::all();
    foreach ($languages as $language) {
      $slug = createSlug($this[$language->code . '_title']);

      $ruleArray[$language->code . '_title'] = [
        'required',
        'max:255',
        function ($attribute, $value, $fail) use ($slug, $language) {
          $cis = EventContent::where('language_id', $language->id)->get();
          foreach ($cis as $ci) {
            if (strtolower($slug) == strtolower($ci->slug)) {
              $fail('The title field must be unique for ' . $language->name . ' language.');
            }
          }
        }
      ];

      if ($bs->event_country_status == 1) {
        $ruleArray[$language->code . '_country'] = 'required_if:event_type,venue';
      }
      if ($bs->event_state_status == 1) {
        $ruleArray[$language->code . '_state'] = 'required_if:event_type,venue';
      }

      $ruleArray[$language->code . '_category_id'] = 'required';
      $ruleArray[$language->code . '_description'] = 'min:30';
      $ruleArray[$language->code . '_address'] = 'required_if:event_type,venue';
      $ruleArray[$language->code . '_city'] = 'required_if:event_type,venue';
    }

    return $ruleArray;
  }

  public function messages()
  {
    $messageArray = [];
    $messageArray['m_start_date.required'] = 'The start date field is required.!';
    $messageArray['m_start_time.required'] = 'The start time field is required.!';
    $messageArray['m_end_date.required'] = 'The end date field is required.!';
    $messageArray['m_end_time.required'] = 'The end time field is required.!';

    $languages = Language::all();
    foreach ($languages as $language) {
      $code = $language->code;
      $langNameText = $language->name . ' language.';
      $messageArray[$code . '_title.required'] = 'The title field is required for ' . $langNameText;

      $messageArray[$code . '_address.required_if'] = 'The address field is required for ' . $langNameText;
      $messageArray[$code . '_country.required_if'] = 'The country field is required for ' . $langNameText;
      $messageArray[$code . '_city.required_if'] = 'The city field is required for ' . $langNameText;
      $messageArray[$code . '_state.required_if'] = 'The state field is required for ' . $langNameText;

      $messageArray[$code . '_category_id.required'] = 'The category field is required for ' . $langNameText;
      $messageArray[$code . '_description.min'] = 'The description must be at least 30 characters for ' . $langNameText;
    }

    return $messageArray;
  }
}
