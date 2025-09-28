// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'user.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

User _$UserFromJson(Map<String, dynamic> json) {
  return User(
    userId: json['userId'],
    name: json['firstName'],
    email: json['email'],
    role: json['role'],
    facebook: json['facebook'],
    twitter: json['twitter'],
    linkedIn: json['linkedIn'],
    biography: json['biography'],
    about: json['about'],
    address: json['address'],
    photo: json['photo'],
  );
}

Map<String, dynamic> _$UserToJson(User instance) => <String, dynamic>{
      'userId': instance.userId,
      'firstName': instance.name,
      'email': instance.email,
      'role': instance.role,
      'facebook': instance.facebook,
      'twitter': instance.twitter,
      'linkedIn': instance.linkedIn,
      'biography': instance.biography,
      'about': instance.about,
      'address': instance.address,
      'photo': instance.photo,
    };
