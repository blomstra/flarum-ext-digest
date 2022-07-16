import app from 'flarum/admin/app';

app.initializers.add('blomstra/digest', () => {
  app.extensionData.for('blomstra-digest').registerSetting({
    setting: 'blomstra-digest.singleDigest',
    type: 'boolean',
    label: app.translator.trans('blomstra-digest.admin.setting.single'),
    help: app.translator.trans('blomstra-digest.admin.setting.singleHelp'),
  });
});
