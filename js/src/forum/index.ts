import app from 'flarum/forum/app';

app.initializers.add('blomstra/digest', () => {
  console.log('[blomstra/digest] Hello, forum!');
});
