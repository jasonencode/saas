import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './vendor/filament/**/*.blade.php',
        './resources/views/**/*.blade.php',

        './vendor/diogogpinto/filament-auth-ui-enhancer/resources/**/*.blade.php',
    ],
}
