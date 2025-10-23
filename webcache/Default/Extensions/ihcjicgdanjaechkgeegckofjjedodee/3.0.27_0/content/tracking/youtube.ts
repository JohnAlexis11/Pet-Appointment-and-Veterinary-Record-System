import {GetTabInfoResponse} from "@/utils/messaging/types";
import {
    getTabInfo,
} from "../helpers";
import {isAdProtectionActive, recordBlockedAd} from "./ad-helpers";
import { clickTheSkip, removeKnownAds, skipToTheEnd } from "@/app/scripts/adblockers/adb-youtube.js";

export async function hideYoutubeAds(pageUrl: string) {
    console.debug("Youtube: hideYoutubeAds", {pageUrl});
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () =>
            afterDOMLoaded(pageUrl)
        );
    } else {
        afterDOMLoaded(pageUrl);
    }
}

async function afterDOMLoaded(pageUrl: string) {
    const tabInfo = await getTabInfo();
    const shouldBlockAds = await isAdProtectionActive(tabInfo.tabId, pageUrl);
    console.log("Youtube: shouldBlockAds", shouldBlockAds);
    if (shouldBlockAds === false) {
        return;
    }

    setInterval(() => {
        clickTheSkip();
        skipToTheEnd();
    }, 500);
    removeKnownAds();
    setInterval(() => removeKnownAds(), 1000);
}

const safariSkipAds = (tab: GetTabInfoResponse) => {
    // Finds hidden "Skip Ads" button and presses it
    const skipBtn = document.getElementsByClassName(
        "ytp-ad-skip-button ytp-button"
    );
    if (skipBtn && skipBtn[0]) {
        (skipBtn[0] as HTMLButtonElement).click();
        recordBlockedAd(tab);
        return; //Return, we want to avoid counting the regular ad twice, they also load the 'ytp-ad-duration-remaining' div
    }
    //Unskippable ads
    const adFrames = document.getElementsByClassName(
        "ytp-ad-duration-remaining"
    );
    if (adFrames && adFrames[0]) {
        const video = document.querySelectorAll("video");
        if (video) {
            // @ts-ignore
            for (const vid of video) {
                if (Number.isNaN(vid.duration)) {
                    continue;
                }
                vid.currentTime = vid.duration - 0.5;
            }
            recordBlockedAd(tab);
        }
    }
};
