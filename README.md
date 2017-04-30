YouTube Import
======================

This [bolt.cm](https://extensions.bolt.cm/view/koolserve/youtube-import) extension. Created to import YouTube videos for [this](https://blingsnitch.com) website. It adds a new nut command `app/nut youtube:import` that will import youtube videos from a playlist into a configured contenttype.

### Installation
1. Login to your Bolt installation
2. Go to "Extend" or "Extras > Extend"
3. Type `YouTubeImport` into the input field
4. Click on the extension name
5. Click on "Browse Versions"
6. Click on "Install This Version" on the latest stable version

### Configuration
You will need a Google API key for this extension to work. You generate a new one from within Google's [developer console](https://console.developers.google.com) with steps on how to do that [here](https://developers.google.com/youtube/v3/getting-started#before-you-start).

You will also need a compatible contenttype. Here is a good starting point
``` yaml
tracks:
    name: Tracks
    singular_name: Track
    fields:
        title:
            type: text
            class: large
            group: content
        image:
            type: image
            upload: tracks
        youtubeid:
            type: text
            label: Youtube ID
```

### Running
To run the import you can use the nut command:

``` bash
app/nut youtube:import
```

This will fetch the configured amount of videos and add those that are missing.

---

### License

This Bolt extension is open-sourced software licensed under the GPL-3.0 License
