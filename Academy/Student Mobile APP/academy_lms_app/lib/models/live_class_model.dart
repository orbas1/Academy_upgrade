class LiveClassModel {
  List<LiveClasses>? liveClasses;
  String? zoomSdk;
  String? zoomSdkClientId;
  String? zoomSdkClientSecret;

  LiveClassModel(
      {this.liveClasses,this.zoomSdk, this.zoomSdkClientId, this.zoomSdkClientSecret});

  LiveClassModel.fromJson(Map<String, dynamic> json) {
    if (json['live_classes'] != null) {
      liveClasses = <LiveClasses>[];
      json['live_classes'].forEach((v) {
        liveClasses!.add(LiveClasses.fromJson(v));
      });
    }
    zoomSdk = json['zoom_sdk'];
    zoomSdkClientId = json['zoom_sdk_client_id'];
    zoomSdkClientSecret = json['zoom_sdk_client_secret'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    if (liveClasses != null) {
      data['live_classes'] = liveClasses!.map((v) => v.toJson()).toList();
    }
    data['zoom_sdk'] = zoomSdk;
    data['zoom_sdk_client_id'] = zoomSdkClientId;
    data['zoom_sdk_client_secret'] = zoomSdkClientSecret;
    return data;
  }
}

class LiveClasses {
  String? classTopic;
  String? provider;
  String? note;
  String? classDateAndTime;
  int? meetingId;
  String? meetingPassword;
  String? startUrl;
  String? joinUrl;

  LiveClasses(
      {this.classTopic,
      this.provider,
      this.note,
      this.classDateAndTime,
      this.meetingId,
      this.meetingPassword,
      this.startUrl,
      this.joinUrl});

  LiveClasses.fromJson(Map<String, dynamic> json) {
    classTopic = json['class_topic'];
    provider = json['provider'];
    note = json['note'];
    classDateAndTime = json['class_date_and_time'];
    meetingId = json['meeting_id'];
    meetingPassword = json['meeting_password'];
    startUrl = json['start_url'];
    joinUrl = json['join_url'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['class_topic'] = classTopic;
    data['provider'] = provider;
    data['note'] = note;
    data['class_date_and_time'] = classDateAndTime;
    data['meeting_id'] = meetingId;
    data['meeting_password'] = meetingPassword;
    data['start_url'] = startUrl;
    data['join_url'] = joinUrl;
    return data;
  }
}
