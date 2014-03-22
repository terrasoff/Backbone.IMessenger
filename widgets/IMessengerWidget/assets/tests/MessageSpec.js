describe("Message", function() {
  var message;

  beforeEach(function() {
      message = new IMessenger.Message();
  });

  it("should be unread", function() {
    expect(message.get('unread')).toBeFalsy();
  });

  //demonstrates use of expected exceptions
  describe("#resume", function() {
    it("should throw an exception if song is already playing", function() {
      player.play(song);

      expect(function() {
        player.resume();
      }).toThrowError("song is already playing");
    });
  });
});

function sum() {
    var sum = 0;
    for (i=0; i<arguments.length; i++) {
        sum += arguments[i];
    }
    return sum;
}