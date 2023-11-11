import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Select from 'flarum/common/components/Select';
import SettingsPage from 'flarum/forum/components/SettingsPage';

app.initializers.add('blomstra/digest', () => {
  extend(SettingsPage.prototype, 'notificationsItems', function (items) {
    items.add(
      'digestFrequency',
      m('.Form-group', [
        m('label', app.translator.trans('blomstra-digest.forum.settings.frequency')),
        Select.component(
          {
            options: {
              immediate: app.translator.trans('blomstra-digest.forum.settings.frequencyOptions.immediate'),
              daily: app.translator.trans('blomstra-digest.forum.settings.frequencyOptions.daily'),
              weekly: app.translator.trans('blomstra-digest.forum.settings.frequencyOptions.weekly'),
            },
            value: this.user!.attribute('digestFrequency') || 'immediate',
            onchange: (value: null | string) => {
              if (value === 'immediate') {
                value = null;
              }

              this.digestFrequencyLoading = true;

              const attributes: any = {
                digestFrequency: value,
              };

              const preferences = this.user.preferences();

              // When enabling digest, turn "notify for all posts" on. We also force this in the backend but this ensures the setting looks correct immediately
              // When disabling digest, we set "notify for all posts" back to off because it's the easiest implementation
              if ('flarum-subscriptions.notify_for_all_posts' in preferences) {
                // Mimics User::savePreferences
                // But we do it here so we can save both the preferences and frequency with one request
                attributes.preferences = {
                  ...preferences,
                  'flarum-subscriptions.notify_for_all_posts': !!value,
                };
              }

              this.user!.save(attributes).then(() => {
                this.digestFrequencyLoading = false;
                m.redraw();
              });
            },
            disabled: this.digestFrequencyLoading,
          },
          app.translator.trans('flarum-subscriptions.forum.settings.follow_after_reply_label')
        ),
      ])
    );

    if (
      items.has('notifyForAllPosts') &&
      this.user.attribute('digestFrequency') &&
      this.user.preferences()?.['flarum-subscriptions.notify_for_all_posts']
    ) {
      // Show visually that flarum-subscriptions.notify_for_all_posts cannot be disabled when digest is scheduled
      items.get('notifyForAllPosts').attrs.disabled = true;
    }

    if (this.user!.attribute('digestFrequency') !== null) {
      items.add(
        'digestHour',
        m('.Form-group', [
          m('label', app.translator.trans('blomstra-digest.forum.settings.hour')),
          Select.component({
            // hours with UTC timezone
            options: Array.from(Array(24).keys()).reduce((options, hour) => {
              options[hour] = hour.toString().padStart(2, '0') + ':00' + ' UTC';
              return options;
            }, {} as any),
            value: this.user!.attribute('digestHour') || '0',
            onchange: (value: null | string) => {
              this.digestHourLoading = true;

              const attributes: any = {
                digestHour: value,
              };

              this.user!.save(attributes).then(() => {
                this.digestHourLoading = false;
                m.redraw();
              });
            },
            disabled: this.digestHourLoading,
          }),
        ])
      );
    }
  });
});
