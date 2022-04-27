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
            value: this.user.attribute('digestFrequency') || 'immediate',
            onchange: (value) => {
              if (value === 'immediate') {
                value = null;
              }

              this.digestFrequencyLoading = true;

              this.user.save({ digestFrequency: value }).then(() => {
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
  });
});
