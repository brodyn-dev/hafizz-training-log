import {eventBus, Events} from "../core/event-bus";

export const isDarkModeEnabled = () => document.documentElement.getAttribute('data-theme') === 'dark';

export default class DarkModeManager {
    constructor() {
        this.storageKey = 'theme';
        this.$themeInputs = document.querySelectorAll('.dark-mode-toggle input[name=theme]');
        this.$themeElement = document.documentElement;
        this.dataAttributeName = 'data-theme';
    }

    attachEventListeners() {
        this.syncCheckedState();

        this.$themeInputs.forEach(($input) => {
            $input.addEventListener('change', () => {
                this.setPreference($input.value);
            });
        });

        document.addEventListener('dropdown:opened', () => this.syncCheckedState());
    }

    syncCheckedState() {
        const preference = localStorage.getItem(this.storageKey) || 'light';

        this.$themeInputs.forEach(($input) => {
            $input.checked = $input.value === preference;
        });
    }

    setPreference(preference) {
        localStorage.setItem(this.storageKey, preference);

        const theme = preference === 'system'
            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : preference;
        if (theme === this.$themeElement.getAttribute(this.dataAttributeName)) {
            return;
        }

        this.$themeElement.setAttribute(this.dataAttributeName, theme);
        eventBus.emit(Events.DARK_MODE_TOGGLED, {darkModeEnabled: theme === 'dark'});
    }
}
