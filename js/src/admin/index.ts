import app from 'flarum/admin/app';

app.initializers.add('blomstra/digest', () => {
  console.log('[blomstra/digest] Hello, admin!');
});
