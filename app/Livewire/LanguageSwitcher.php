<?php

namespace App\Livewire;

use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public bool $showDropdown = false;

    /** @var array<string, array{label: string, native: string}> */
    public array $languages;

    public string $current;

    public function mount(): void
    {
        $this->languages = [
            'zh_CN' => ['label' => __('中文'), 'native' => __('简体中文')],
            'en'    => ['label' => 'English', 'native' => 'English'],
        ];
        $this->current = App::getLocale();
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = !$this->showDropdown;
    }

    public function switchTo(string $locale): void
    {
        if (!in_array($locale, SetLocale::SUPPORTED, true)) {
            return;
        }

        // Store in session
        Session::put('locale', $locale);
        App::setLocale($locale);

        // Persist for logged-in users
        if ($user = auth()->user()) {
            $user->update(['locale' => $locale]);
        }

        $this->current = $locale;
        $this->showDropdown = false;

        // Redirect to refresh the page with new locale
        $this->redirect(request()->header('Referer', url()->previous() ?: '/'), navigate: false);
    }

    public function render()
    {
        return view('livewire.language-switcher');
    }
}
