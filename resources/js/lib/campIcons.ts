import {
    Anchor,
    Backpack,
    Bike,
    Church,
    Compass,
    Cross,
    Flame,
    Gamepad2,
    Heart,
    MapPin,
    Mountain,
    Music,
    Palette,
    Rocket,
    Ship,
    Sparkles,
    Star,
    Sun,
    Tent,
    TreePine,
    Waves,
} from '@lucide/vue';
import type { Component } from 'vue';

// Selectable icons for a camp's identity.
export const CAMP_ICONS: Record<string, Component> = {
    tent: Tent,
    flame: Flame,
    sun: Sun,
    tree: TreePine,
    mountain: Mountain,
    waves: Waves,
    backpack: Backpack,
    compass: Compass,
    star: Star,
    heart: Heart,
    music: Music,
    ship: Ship,
    bike: Bike,
    church: Church,
    cross: Cross,
    game: Gamepad2,
    rocket: Rocket,
    sparkles: Sparkles,
    palette: Palette,
    anchor: Anchor,
    pin: MapPin,
};

export const CAMP_ICON_NAMES = Object.keys(CAMP_ICONS);

export function campIcon(name?: string | null): Component {
    return CAMP_ICONS[name ?? ''] ?? Tent;
}
